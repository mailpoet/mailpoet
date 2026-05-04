<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Cron\Workers\BulkConfirmationEmailResend;
use MailPoet\Entities\LogEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Logging\LogRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class BulkConfirmationEmailResender {
  public const BULK_CONFIRMATION_RESEND_LIMIT = 20;
  public const RECENT_CONFIRMATION_RESEND_INTERVAL_DAYS = 7;
  public const BULK_CONFIRMATION_MAX_SUBSCRIBER_AGE_DAYS = 90;

  private const AUDIT_LOG_MESSAGE_QUEUED = 'Bulk confirmation email resend queued.';
  private const LOG_LEVEL_INFO = 200;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var SubscriberListingRepository */
  private $subscriberListingRepository;

  /** @var LogRepository */
  private $logRepository;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings,
    SubscriberListingRepository $subscriberListingRepository,
    LogRepository $logRepository,
    EntityManager $entityManager
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
    $this->subscriberListingRepository = $subscriberListingRepository;
    $this->logRepository = $logRepository;
    $this->entityManager = $entityManager;
  }

  public function canCurrentUserResend(): bool {
    return $this->wp->currentUserCan('manage_options');
  }

  public function isSignupConfirmationEnabled(): bool {
    return (bool)$this->settings->get('signup_confirmation.enabled', true);
  }

  public function getConfirmationDisabledMessage(): string {
    $errorMessage = __('Sign-up confirmation is disabled in your [link]MailPoet settings[/link]. Please enable it to resend confirmation emails or update your subscriber’s status manually.', 'mailpoet');
    return Helpers::replaceLinkTags($errorMessage, 'admin.php?page=mailpoet-settings#/signup');
  }

  /**
   * @param array<string, mixed> $requestData
   * @return array{selected_count: int, eligible_count: int, queued_count: int, skipped_count: int, skipped_by_reason: array<string, int>, task_id: int|null, message: string}
   */
  public function queue(ListingDefinition $definition, array $requestData): array {
    $now = Carbon::now()->millisecond(0);
    $queueData = $this->subscriberListingRepository->getConfirmationEmailResendQueueData(
      $definition,
      (clone $now)->subDays(self::RECENT_CONFIRMATION_RESEND_INTERVAL_DAYS),
      (clone $now)->subDays(self::BULK_CONFIRMATION_MAX_SUBSCRIBER_AGE_DAYS),
      ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS,
      self::BULK_CONFIRMATION_RESEND_LIMIT,
      $this->hasExplicitSelection($requestData)
    );

    $queuedIds = $queueData['queued_ids'];
    $task = null;
    if ($queuedIds) {
      $task = $this->createTask($queuedIds, $queueData, $requestData, $now);
    } else {
      $this->saveQueueAuditLog(null, $queueData, $requestData);
    }

    $queuedCount = count($queuedIds);
    return [
      'selected_count' => $queueData['selected_count'],
      'eligible_count' => $queueData['eligible_count'],
      'queued_count' => $queuedCount,
      'skipped_count' => max(0, $queueData['selected_count'] - $queuedCount),
      'skipped_by_reason' => $queueData['skipped_by_reason'],
      'task_id' => $task instanceof ScheduledTaskEntity ? $task->getId() : null,
      'message' => $queuedCount > 0
        ? __('Confirmation emails are being resent.', 'mailpoet')
        : __(
          'No confirmation emails were resent. The selected subscribers could not receive another confirmation email right now.',
          'mailpoet'
        ),
    ];
  }

  /**
   * @param int[] $queuedIds
   * @param array{selected_count: int, eligible_count: int, queued_ids: int[], skipped_by_reason: array<string, int>} $queueData
   * @param array<string, mixed> $requestData
   */
  private function createTask(array $queuedIds, array $queueData, array $requestData, Carbon $now): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $this->entityManager->transactional(function (EntityManager $entityManager) use ($task, $queuedIds, $queueData, $requestData, $now) {
      $task->setType(BulkConfirmationEmailResend::TASK_TYPE);
      $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
      $task->setScheduledAt($now);
      $task->setPriority(ScheduledTaskEntity::PRIORITY_HIGH);
      $task->setMeta([
        'requested_by' => (int)$this->wp->getCurrentUserId(),
        'selected_count' => $queueData['selected_count'],
        'eligible_count' => $queueData['eligible_count'],
        'queued_count' => count($queuedIds),
        'skipped_by_reason' => $queueData['skipped_by_reason'],
        'constants' => $this->getAuditConstants(),
        'request' => $this->getRequestSummary($requestData),
      ]);
      $entityManager->persist($task);

      foreach ($queuedIds as $subscriberId) {
        /** @var SubscriberEntity $subscriberReference */
        $subscriberReference = $entityManager->getReference(SubscriberEntity::class, $subscriberId);
        $entityManager->persist(new ScheduledTaskSubscriberEntity(
          $task,
          $subscriberReference
        ));
      }

      $entityManager->flush();
      $this->saveQueueAuditLog($task, $queueData, $requestData);
    });

    return $task;
  }

  /**
   * @param array{selected_count: int, eligible_count: int, queued_ids: int[], skipped_by_reason: array<string, int>} $queueData
   * @param array<string, mixed> $requestData
   */
  private function saveQueueAuditLog(?ScheduledTaskEntity $task, array $queueData, array $requestData): void {
    $log = new LogEntity();
    $log->setName(LoggerFactory::TOPIC_API);
    $log->setLevel(self::LOG_LEVEL_INFO);
    $log->setMessage(self::AUDIT_LOG_MESSAGE_QUEUED);
    $log->setRawMessage(self::AUDIT_LOG_MESSAGE_QUEUED);
    $log->setContext([
      'action' => 'bulk_confirmation_email_resend_queued',
      'requested_by' => (int)$this->wp->getCurrentUserId(),
      'task_id' => $task instanceof ScheduledTaskEntity ? $task->getId() : null,
      'selected_count' => $queueData['selected_count'],
      'eligible_count' => $queueData['eligible_count'],
      'queued_count' => count($queueData['queued_ids']),
      'skipped_by_reason' => $queueData['skipped_by_reason'],
      'constants' => $this->getAuditConstants(),
      'request' => $this->getRequestSummary($requestData),
    ]);
    $this->logRepository->saveLog($log);
  }

  /**
   * @return array<string, int>
   */
  private function getAuditConstants(): array {
    return [
      'bulk_confirmation_resend_limit' => self::BULK_CONFIRMATION_RESEND_LIMIT,
      'recent_confirmation_resend_interval_days' => self::RECENT_CONFIRMATION_RESEND_INTERVAL_DAYS,
      'bulk_confirmation_max_subscriber_age_days' => self::BULK_CONFIRMATION_MAX_SUBSCRIBER_AGE_DAYS,
      'max_confirmation_emails' => ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS,
    ];
  }

  /**
   * @param array<string, mixed> $requestData
   * @return array<string, mixed>
   */
  private function getRequestSummary(array $requestData): array {
    $listing = isset($requestData['listing']) && is_array($requestData['listing']) ? $requestData['listing'] : [];
    return [
      'group' => $listing['group'] ?? null,
      'has_selection' => !empty($listing['selection']),
      'selection_count' => isset($listing['selection']) && is_array($listing['selection']) ? count($listing['selection']) : 0,
      'has_search' => !empty($listing['search']),
      'filter_keys' => isset($listing['filter']) && is_array($listing['filter']) ? array_keys($listing['filter']) : [],
    ];
  }

  /**
   * @param array<string, mixed> $requestData
   */
  private function hasExplicitSelection(array $requestData): bool {
    $listing = isset($requestData['listing']) && is_array($requestData['listing']) ? $requestData['listing'] : [];
    return array_key_exists('selection', $listing);
  }
}
