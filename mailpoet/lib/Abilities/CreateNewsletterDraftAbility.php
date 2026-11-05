<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Dependency_Check;
use MailPoet\Config\AccessControl;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Templates\TemplatesController;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\NewsletterDeleteController;
use MailPoet\Newsletter\NewsletterSaveController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/** @phpstan-import-type PreparedDraft from NewsletterDrafts */
class CreateNewsletterDraftAbility implements Ability {
  public const NAME = 'mailpoet/create-newsletter-draft';

  private const CLIENT_REFERENCE_META_KEY = '_mailpoet_client_reference';
  // Every status except trash, so a trashed draft doesn't block a new one. "any" won't do
  // because it leaves out "sent", and a retry would then create a second newsletter.
  private const CLIENT_REFERENCE_POST_STATUSES = ['draft', 'pending', 'private', 'publish', 'future', NewsletterEntity::STATUS_SENT];

  /** @var NewsletterDrafts */
  private $drafts;

  /** @var NewsletterSaveController */
  private $saveController;

  /** @var NewsletterDeleteController */
  private $deleteController;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var TemplatesController */
  private $templatesController;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewsletterDrafts $drafts,
    NewsletterSaveController $saveController,
    NewsletterDeleteController $deleteController,
    NewslettersRepository $newslettersRepository,
    TemplatesController $templatesController,
    LoggerFactory $loggerFactory,
    WPFunctions $wp
  ) {
    $this->drafts = $drafts;
    $this->saveController = $saveController;
    $this->deleteController = $deleteController;
    $this->newslettersRepository = $newslettersRepository;
    $this->templatesController = $templatesController;
    $this->loggerFactory = $loggerFactory;
    $this->wp = $wp;
  }

  public function getName(): string {
    return self::NAME;
  }

  public function getRegistrationArgs(): array {
    return [
      'label' => __('Create MailPoet newsletter draft', 'mailpoet'),
      'description' => __('Creates one standard MailPoet newsletter as a draft from structured content and returns edit_url for a person to review. It never sends or schedules. Use it only when the user asks for a newsletter draft. First call mailpoet/get-newsletter-draft-options for the lists and site colors. Make it look designed: use sections with background colors, a colored button, images and short paragraphs.', 'mailpoet'),
      'input_schema' => $this->getInputSchema(),
      'output_schema' => $this->getOutputSchema(),
      'execute_callback' => [$this, 'execute'],
      'permission_callback' => [$this, 'hasPermission'],
      'meta' => AbilitiesRegistrar::getWriteMeta(),
    ];
  }

  public function hasPermission(): bool {
    return $this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_EMAILS);
  }

  /**
   * @param array|null $input
   * @return array|\WP_Error
   */
  public function execute($input = null) {
    $input = (array)$input;
    if (!Email_Editor_Container::container()->get(Dependency_Check::class)->are_dependencies_met()) {
      return new \WP_Error(
        'mailpoet_email_editor_unavailable',
        __('The block email editor is not available on this site, so no draft was created.', 'mailpoet'),
        ['status' => 409]
      );
    }

    $prepared = $this->drafts->prepareInput($input);
    if ($prepared['errors']) {
      return $this->drafts->getInvalidInputError($prepared['errors']);
    }

    $clientReference = isset($input['client_reference']) ? (string)$input['client_reference'] : null;
    $existing = $clientReference === null ? null : $this->findByClientReference($clientReference);
    if ($existing) {
      return array_merge($this->drafts->buildOutput($existing), ['created' => false, 'warnings' => $prepared['warnings']]);
    }

    $newsletter = $this->createDraft($prepared, $clientReference);
    if (!$newsletter) {
      return new \WP_Error(
        'mailpoet_draft_not_created',
        __('The draft could not be saved. Try again, or ask the site owner to check the MailPoet logs.', 'mailpoet'),
        ['status' => 500]
      );
    }
    return array_merge($this->drafts->buildOutput($newsletter), ['created' => true, 'warnings' => $prepared['warnings']]);
  }

  /** @param PreparedDraft $prepared */
  private function createDraft(array $prepared, ?string $clientReference): ?NewsletterEntity {
    $newsletter = null;
    try {
      $newsletter = $this->saveController->save([
        'subject' => (string)$prepared['subject'],
        'preheader' => (string)$prepared['preheader'],
        'type' => NewsletterEntity::TYPE_STANDARD,
        'new_editor' => true,
        'segments' => $prepared['segments'] ?? [],
      ]);
      // The save controller doesn't report a failed post insert, it leaves post ID 0.
      $postId = (int)$newsletter->getWpPostId();
      if ($postId <= 0) {
        throw new \RuntimeException('The newsletter has no post.');
      }
      $this->drafts->updatePost($postId, [
        'post_title' => (string)$prepared['subject'],
        'post_content' => (string)$prepared['markup'],
      ]);
      $this->wp->updatePostMeta($postId, '_wp_page_template', $this->templatesController->getDefaultTemplateSlug());
      if ($clientReference !== null) {
        $this->wp->updatePostMeta($postId, self::CLIENT_REFERENCE_META_KEY, $this->wp->wpSlash($clientReference));
      }
      return $newsletter;
    } catch (\Throwable $e) {
      $this->logFailure($e);
      if ($newsletter) {
        $this->deleteNewsletter($newsletter);
      }
      return null;
    }
  }

  private function deleteNewsletter(NewsletterEntity $newsletter): void {
    try {
      // A failed post insert leaves post ID 0, which core refuses to delete.
      if (!$newsletter->getWpPostId()) {
        $newsletter->setWpPost(null);
        $this->newslettersRepository->flush();
      }
      $this->deleteController->bulkDelete([(int)$newsletter->getId()]);
    } catch (\Throwable $e) {
      $this->logFailure($e);
    }
  }

  private function logFailure(\Throwable $error): void {
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->error(
      'Creating a newsletter draft through an ability failed',
      ['error' => $error->getMessage()]
    );
  }

  private function findByClientReference(string $clientReference): ?NewsletterEntity {
    $postIds = $this->wp->getPosts([
      'post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
      'post_status' => self::CLIENT_REFERENCE_POST_STATUSES,
      'posts_per_page' => 1,
      // Duplicating a newsletter copies its post meta, so the oldest post is the original.
      'orderby' => 'ID',
      'order' => 'ASC',
      'fields' => 'ids',
      'meta_key' => self::CLIENT_REFERENCE_META_KEY,
      'meta_value' => $clientReference,
    ]);
    if (!$postIds) {
      return null;
    }
    return $this->newslettersRepository->findOneBy(['wpPost' => $postIds[0]]);
  }

  private function getInputSchema(): array {
    return [
      'type' => 'object',
      'required' => ['subject', 'blocks'],
      'additionalProperties' => false,
      'properties' => array_merge($this->drafts->getInputProperties(), [
        'client_reference' => [
          'type' => 'string',
          'minLength' => 1,
          'maxLength' => 64,
          'description' => __('Your own ID for this draft. A repeated call with the same value returns the same draft instead of creating another.', 'mailpoet'),
        ],
      ]),
    ];
  }

  private function getOutputSchema(): array {
    return [
      'type' => 'object',
      'required' => ['id', 'wp_post_id', 'subject', 'status', 'edit_url', 'created', 'list_ids', 'version', 'warnings'],
      'additionalProperties' => false,
      'properties' => array_merge($this->drafts->getOutputProperties(), [
        'status' => [
          'type' => 'string',
          'description' => __('The newsletter status. A new draft is always draft, but a repeated client_reference returns the current status.', 'mailpoet'),
        ],
        'warnings' => $this->drafts->getWarningsProperty(),
        'created' => [
          'type' => 'boolean',
          'description' => __('False when client_reference matched an existing newsletter.', 'mailpoet'),
        ],
      ]),
    ];
  }
}
