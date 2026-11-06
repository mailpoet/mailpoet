<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Config\AccessControl;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\NewsletterSaveController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class UpdateNewsletterDraftAbility implements Ability {
  public const NAME = 'mailpoet/update-newsletter-draft';

  /** @var NewsletterDrafts */
  private $drafts;

  /** @var NewsletterDraftReader */
  private $reader;

  /** @var NewsletterSaveController */
  private $saveController;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewsletterDrafts $drafts,
    NewsletterDraftReader $reader,
    NewsletterSaveController $saveController,
    LoggerFactory $loggerFactory,
    WPFunctions $wp
  ) {
    $this->drafts = $drafts;
    $this->reader = $reader;
    $this->saveController = $saveController;
    $this->loggerFactory = $loggerFactory;
    $this->wp = $wp;
  }

  public function getName(): string {
    return self::NAME;
  }

  public function getRegistrationArgs(): array {
    return [
      'label' => __('Update MailPoet newsletter draft', 'mailpoet'),
      'description' => __('Changes the subject, preheader, blocks or list_ids of a newsletter draft. It never sends or schedules. blocks replaces all the blocks, and there is no undo, so send the full content from mailpoet/get-newsletter-draft with your changes. Pass the version you read. It is refused if a person changed the draft since then, so read it again and tell them.', 'mailpoet'),
      'input_schema' => [
        'type' => 'object',
        'required' => ['id', 'version'],
        'additionalProperties' => false,
        'properties' => array_merge([
          'id' => ['type' => 'integer'],
          'version' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 64],
        ], $this->drafts->getInputProperties()),
      ],
      'output_schema' => [
        'type' => 'object',
        'additionalProperties' => false,
        'properties' => array_merge($this->drafts->getOutputProperties(), ['warnings' => $this->drafts->getWarningsProperty()]),
      ],
      'execute_callback' => [$this, 'execute'],
      'permission_callback' => [$this, 'hasPermission'],
      'meta' => AbilitiesRegistrar::getWriteMeta(true),
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
    $newsletter = $this->drafts->findNewsletter((int)$input['id']);
    if (!$newsletter) {
      return $this->drafts->getNotFoundError();
    }
    $reason = $this->drafts->getNotEditableReason($newsletter, $this->reader->read($this->drafts->getContent($newsletter)));
    if ($reason !== null) {
      return new \WP_Error('mailpoet_draft_not_editable', $reason, ['status' => 409]);
    }
    if ($input['version'] !== $this->drafts->getVersion($newsletter)) {
      return new \WP_Error(
        'mailpoet_draft_changed',
        __('The draft changed since you read it, and nothing was saved. Read it again with mailpoet/get-newsletter-draft and tell the user what changed.', 'mailpoet'),
        ['status' => 409]
      );
    }

    $prepared = $this->drafts->prepareInput($input);
    if ($prepared['errors']) {
      return $this->drafts->getInvalidInputError($prepared['errors']);
    }

    try {
      $post = [];
      if ($prepared['subject'] !== null && $this->campaignNameFollowsSubject($newsletter)) {
        $post['post_title'] = $prepared['subject'];
      }
      if ($prepared['markup'] !== null) {
        $post['post_content'] = $prepared['markup'];
      }
      if ($post) {
        $this->drafts->updatePost((int)$newsletter->getWpPostId(), $post);
      }
      $saved = $this->saveController->save(['id' => $newsletter->getId()] + array_filter([
        'subject' => $prepared['subject'],
        'preheader' => $prepared['preheader'],
        'segments' => $prepared['segments'],
      ], function ($value): bool {
        return $value !== null;
      }));
      return array_merge($this->drafts->buildOutput($saved), ['warnings' => $prepared['warnings']]);
    } catch (\Throwable $e) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->error(
        'Updating a newsletter draft through an ability failed',
        ['error' => $e->getMessage()]
      );
      return new \WP_Error(
        'mailpoet_draft_not_updated',
        __('The draft could not be saved, and part of the change may be there. Read it again before you retry, or ask the site owner to check the MailPoet logs.', 'mailpoet'),
        ['status' => 500]
      );
    }
  }

  /** The same rule as the editor's: the name follows the subject until a person picks one. */
  private function campaignNameFollowsSubject(NewsletterEntity $newsletter): bool {
    $name = $this->wp->getPostField('post_title', (int)$newsletter->getWpPostId());
    return in_array($name, ['', __('New Email', 'mailpoet'), $newsletter->getSubject()], true);
  }
}
