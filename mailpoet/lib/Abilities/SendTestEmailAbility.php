<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Config\AccessControl;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\InvalidStateException;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\Preview\SendPreviewController;
use MailPoet\Newsletter\Preview\SendPreviewException;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class SendTestEmailAbility implements Ability {
  public const NAME = 'mailpoet/send-test-email';

  /** @var NewsletterDrafts */
  private $drafts;

  /** @var SendPreviewController */
  private $sendPreviewController;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewsletterDrafts $drafts,
    SendPreviewController $sendPreviewController,
    SubscribersFeature $subscribersFeature,
    LoggerFactory $loggerFactory,
    WPFunctions $wp
  ) {
    $this->drafts = $drafts;
    $this->sendPreviewController = $sendPreviewController;
    $this->subscribersFeature = $subscribersFeature;
    $this->loggerFactory = $loggerFactory;
    $this->wp = $wp;
  }

  public function getName(): string {
    return self::NAME;
  }

  public function getRegistrationArgs(): array {
    return [
      'label' => __('Send MailPoet newsletter draft test email', 'mailpoet'),
      'description' => __('Sends one test email of a newsletter draft to the email address of the current user, the same as the test email in the editor. It never sends to other addresses or to lists. Find ids in mailpoet/get-newsletter-draft-options.', 'mailpoet'),
      'input_schema' => [
        'type' => 'object',
        'required' => ['id'],
        'additionalProperties' => false,
        'properties' => [
          'id' => ['type' => 'integer'],
        ],
      ],
      'output_schema' => [
        'type' => 'object',
        'additionalProperties' => false,
        'properties' => [
          'id' => ['type' => 'integer'],
          'subject' => ['type' => 'string'],
          'sent_to' => ['type' => 'string', 'description' => __('The address the test email went to.', 'mailpoet')],
        ],
      ],
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
    $newsletter = $this->drafts->findNewsletter((int)((array)$input)['id']);
    if (!$newsletter) {
      return $this->drafts->getNotFoundError();
    }
    if ($newsletter->getStatus() !== NewsletterEntity::STATUS_DRAFT || $newsletter->getLatestQueue()) {
      return new \WP_Error(
        'mailpoet_not_a_draft',
        __('This newsletter is no longer a draft, so no test email was sent.', 'mailpoet'),
        ['status' => 409]
      );
    }
    $recipient = (string)$this->wp->wpGetCurrentUser()->user_email;

    try {
      $this->sendPreviewController->sendPreview($newsletter, $recipient);
    } catch (SendPreviewException $e) {
      // MailPoet Sending Service errors are HTML notices with links and buttons for the admin.
      $reason = html_entity_decode($this->wp->wpStripAllTags($e->getMessage(), true), ENT_QUOTES, 'UTF-8');
      return $this->getNotSentError(SendingStatusAbility::maskPersonalData($reason));
    } catch (InvalidStateException $e) {
      // The mailer can't be built, e.g. no sending method or sender. The messages are written for users.
      return $this->getNotSentError($e->getMessage());
    } catch (\Throwable $e) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->error(
        'Sending a test email of a newsletter draft through an ability failed',
        ['error' => $e->getMessage()]
      );
      return new \WP_Error(
        'mailpoet_test_email_not_sent',
        __('The test email could not be created. Ask the site owner to check the MailPoet logs.', 'mailpoet'),
        ['status' => 500]
      );
    }

    return [
      'id' => (int)$newsletter->getId(),
      'subject' => $newsletter->getSubject(),
      'sent_to' => $recipient,
    ];
  }

  /** The reason, and what to check, in one message: users who manage emails can't read the sending status. */
  private function getNotSentError(string $reason): \WP_Error {
    if (MailerLog::isSendingPaused()) {
      $hint = __('Sending is paused. An administrator has to resolve the problem and resume sending in MailPoet.', 'mailpoet');
    } elseif ($this->subscribersFeature->check() || $this->subscribersFeature->checkEmailVolumeLimitIsReached()) {
      $hint = __('The subscriber or email limit of the MailPoet plan is reached. An administrator can check the plan in MailPoet.', 'mailpoet');
    } else {
      $hint = __('An administrator can check the sender and the sending method in MailPoet > Settings, or with mailpoet/get-sending-status.', 'mailpoet');
    }
    $reason = rtrim($reason);
    if (!in_array(substr($reason, -1), ['.', '!', '?'], true)) {
      $reason .= '.';
    }
    return new \WP_Error(
      'mailpoet_test_email_not_sent',
      $reason . ' ' . $hint,
      ['status' => 502]
    );
  }
}
