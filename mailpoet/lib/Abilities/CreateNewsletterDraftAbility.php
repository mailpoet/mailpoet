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

class CreateNewsletterDraftAbility implements Ability {
  public const NAME = 'mailpoet/create-newsletter-draft';

  private const CLIENT_REFERENCE_META_KEY = '_mailpoet_client_reference';
  // Every status except trash, so a trashed draft doesn't block a new one. "any" won't do
  // because it leaves out "sent", and a retry would then create a second newsletter.
  private const CLIENT_REFERENCE_POST_STATUSES = ['draft', 'pending', 'private', 'publish', 'future', NewsletterEntity::STATUS_SENT];

  /** @var NewsletterDraftBlocks */
  private $draftBlocks;

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
    NewsletterDraftBlocks $draftBlocks,
    NewsletterSaveController $saveController,
    NewsletterDeleteController $deleteController,
    NewslettersRepository $newslettersRepository,
    TemplatesController $templatesController,
    LoggerFactory $loggerFactory,
    WPFunctions $wp
  ) {
    $this->draftBlocks = $draftBlocks;
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
      'description' => __('Creates one standard MailPoet newsletter as a draft from structured content and returns edit_url for a person to review. It never sends, schedules or chooses recipients. Use it only when the user asks for a newsletter draft.', 'mailpoet'),
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

    $draft = $this->draftBlocks->prepare((array)($input['blocks'] ?? []));
    $errors = $draft['errors'];
    $warnings = $draft['warnings'];

    $subjectChanged = false;
    $subject = $this->draftBlocks->sanitizeText((string)($input['subject'] ?? ''), $subjectChanged);
    if ($subjectChanged) {
      array_unshift($warnings, $this->draftBlocks->getRemovedContentWarning('subject'));
    }
    if ($subject === '') {
      array_unshift($errors, ['field' => 'subject', 'message' => __('subject is empty after removing unsupported content.', 'mailpoet')]);
    }

    $preheaderChanged = false;
    $preheader = $this->draftBlocks->sanitizeText((string)($input['preheader'] ?? ''), $preheaderChanged);
    if ($preheaderChanged) {
      array_unshift($warnings, $this->draftBlocks->getRemovedContentWarning('preheader'));
    }

    if ($errors) {
      return new \WP_Error('mailpoet_invalid_blocks', $this->getErrorMessage($errors), ['status' => 400, 'errors' => $errors]);
    }

    $clientReference = isset($input['client_reference']) ? (string)$input['client_reference'] : null;
    $existing = $clientReference === null ? null : $this->findByClientReference($clientReference);
    if ($existing) {
      return $this->buildOutput($existing, false, $warnings);
    }

    $newsletter = $this->createDraft($subject, $preheader, $draft['markup'], $clientReference);
    if (!$newsletter) {
      return new \WP_Error(
        'mailpoet_draft_not_created',
        __('The draft could not be saved. Try again, or ask the site owner to check the MailPoet logs.', 'mailpoet'),
        ['status' => 500]
      );
    }
    return $this->buildOutput($newsletter, true, $warnings);
  }

  private function createDraft(string $subject, string $preheader, string $content, ?string $clientReference): ?NewsletterEntity {
    $newsletter = null;
    try {
      $newsletter = $this->saveController->save([
        'subject' => $subject,
        'preheader' => $preheader,
        'type' => NewsletterEntity::TYPE_STANDARD,
        'new_editor' => true,
      ]);
      // The save controller doesn't report a failed post insert, it leaves post ID 0.
      $postId = (int)$newsletter->getWpPostId();
      if ($postId <= 0) {
        throw new \RuntimeException('The newsletter has no post.');
      }
      $result = $this->wp->wpUpdatePost($this->wp->wpSlash([
        'ID' => $postId,
        'post_title' => $subject,
        'post_content' => $content,
      ]), true);
      if ($this->wp->isWpError($result)) {
        throw new \RuntimeException('The post was not updated.');
      }
      $this->wp->updatePostMeta($postId, '_wp_page_template', $this->templatesController->getDefaultTemplateSlug());
      if ($clientReference !== null) {
        $this->wp->updatePostMeta($postId, self::CLIENT_REFERENCE_META_KEY, $this->wp->wpSlash($clientReference));
      }
      return $newsletter;
    } catch (\Throwable $e) {
      $this->logFailure($e);
      $this->deleteNewsletter($newsletter);
      return null;
    }
  }

  private function deleteNewsletter(?NewsletterEntity $newsletter): void {
    $id = $newsletter ? $newsletter->getId() : null;
    if (!$newsletter || !$id) {
      return;
    }
    try {
      // A failed post insert leaves post ID 0, which core refuses to delete.
      if (!$newsletter->getWpPostId()) {
        $newsletter->setWpPost(null);
        $this->newslettersRepository->flush();
      }
      $this->deleteController->bulkDelete([$id]);
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

  /** @param array<int, array{field: string, message: string}> $errors */
  private function getErrorMessage(array $errors): string {
    $messages = array_map(function (array $error): string {
      return $error['field'] . ': ' . $error['message'];
    }, $errors);
    return __('No draft was created. Fix these fields and try again.', 'mailpoet') . ' ' . implode(' ', $messages);
  }

  /** @param string[] $warnings */
  private function buildOutput(NewsletterEntity $newsletter, bool $created, array $warnings): array {
    $postId = (int)$newsletter->getWpPostId();
    return [
      'id' => (int)$newsletter->getId(),
      'wp_post_id' => $postId,
      'subject' => $newsletter->getSubject(),
      'status' => $newsletter->getStatus(),
      'edit_url' => $this->wp->adminUrl('post.php?post=' . $postId . '&action=edit'),
      'created' => $created,
      'warnings' => $warnings,
    ];
  }

  private function getInputSchema(): array {
    return [
      'type' => 'object',
      'required' => ['subject', 'blocks'],
      'additionalProperties' => false,
      'properties' => [
        'subject' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 250],
        'preheader' => ['type' => 'string', 'maxLength' => 250],
        'blocks' => [
          'type' => 'array',
          'minItems' => 1,
          'maxItems' => 50,
          'items' => [
            'type' => 'object',
            'required' => ['type'],
            'additionalProperties' => false,
            'properties' => [
              'type' => [
                'type' => 'string',
                'enum' => [
                  NewsletterDraftBlocks::TYPE_HEADING,
                  NewsletterDraftBlocks::TYPE_PARAGRAPH,
                  NewsletterDraftBlocks::TYPE_BUTTON,
                  NewsletterDraftBlocks::TYPE_IMAGE,
                ],
                'description' => __('heading: text (at most 300 characters), optional level. paragraph: text, which may use strong, em and a href. button: text (at most 100 characters), url. image: url, alt. Other fields are rejected.', 'mailpoet'),
              ],
              'text' => ['type' => 'string', 'maxLength' => 5000],
              'level' => ['type' => 'integer', 'enum' => [1, 2, 3]],
              'url' => ['type' => 'string', 'format' => 'uri'],
              'alt' => ['type' => 'string', 'maxLength' => 300],
            ],
          ],
        ],
        'client_reference' => [
          'type' => 'string',
          'minLength' => 1,
          'maxLength' => 64,
          'description' => __('Your own ID for this draft. A repeated call with the same value returns the same draft instead of creating another.', 'mailpoet'),
        ],
      ],
    ];
  }

  private function getOutputSchema(): array {
    return [
      'type' => 'object',
      'required' => ['id', 'wp_post_id', 'subject', 'status', 'edit_url', 'created', 'warnings'],
      'additionalProperties' => false,
      'properties' => [
        'id' => ['type' => 'integer'],
        'wp_post_id' => ['type' => 'integer'],
        'subject' => ['type' => 'string'],
        'status' => [
          'type' => 'string',
          'description' => __('The newsletter status. A new draft is always draft, but a repeated client_reference returns the current status.', 'mailpoet'),
        ],
        'edit_url' => ['type' => 'string'],
        'created' => [
          'type' => 'boolean',
          'description' => __('False when client_reference matched an existing newsletter.', 'mailpoet'),
        ],
        'warnings' => [
          'type' => 'array',
          'items' => ['type' => 'string'],
          'description' => __('Which fields lost personalization tags, shortcodes or HTML comments. Tell the user about them.', 'mailpoet'),
        ],
      ],
    ];
  }
}
