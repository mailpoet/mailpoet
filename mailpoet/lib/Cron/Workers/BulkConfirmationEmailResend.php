<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\LogEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Logging\LogRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscriberMover;
use MailPoet\Subscribers\BulkConfirmationEmailResender;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Subscribers\BatchIterator;
use MailPoetVendor\Carbon\Carbon;

class BulkConfirmationEmailResend extends SimpleWorker {
  const TASK_TYPE = 'bulk_confirmation_email_resend';
  const AUTOMATIC_SCHEDULING = false;
  const SUPPORT_MULTIPLE_INSTANCES = false;
  const BATCH_SIZE = 20;

  private const AUDIT_LOG_MESSAGE_COMPLETED = 'Bulk confirmation email resend completed.';
  private const LOG_LEVEL_INFO = 200;

  /** @var ConfirmationEmailMailer */
  private $confirmationEmailMailer;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var ScheduledTaskSubscriberMover */
  private $scheduledTaskSubscriberMover;

  /** @var LogRepository */
  private $logRepository;

  public function __construct(
    ConfirmationEmailMailer $confirmationEmailMailer,
    SubscribersRepository $subscribersRepository,
    ScheduledTaskSubscriberMover $scheduledTaskSubscriberMover,
    LogRepository $logRepository
  ) {
    $this->confirmationEmailMailer = $confirmationEmailMailer;
    $this->subscribersRepository = $subscribersRepository;
    $this->scheduledTaskSubscriberMover = $scheduledTaskSubscriberMover;
    $this->logRepository = $logRepository;
    parent::__construct();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $subscriberBatches = new BatchIterator($task->getId(), self::BATCH_SIZE);
    $sentCount = 0;
    $failedCount = 0;
    $skippedByReason = [];
    $oldestLifecycleDate = Carbon::now()->subDays(BulkConfirmationEmailResender::BULK_CONFIRMATION_MAX_SUBSCRIBER_AGE_DAYS)->millisecond(0);

    foreach ($subscriberBatches as $subscriberIds) {
      $this->cronHelper->enforceExecutionLimit($timer);
      $sentIds = [];
      foreach ($subscriberIds as $subscriberId) {
        $subscriber = $this->subscribersRepository->findOneById((int)$subscriberId);
        if (!$subscriber instanceof SubscriberEntity) {
          $this->saveSkipped($task, (int)$subscriberId, 'not_found', $skippedByReason);
          $failedCount++;
          continue;
        }

        try {
          $result = $this->confirmationEmailMailer->sendAdminConfirmationEmail($subscriber, $oldestLifecycleDate);
        } catch (\Throwable $throwable) {
          $this->scheduledTaskSubscriberMover->moveFailedToLog($task, (int)$subscriberId, 'send_failed:mailer_error');
          $failedCount++;
          continue;
        }

        if ($result['status'] === 'sent') {
          $sentIds[] = (int)$subscriberId;
          $sentCount++;
        } elseif ($result['status'] === 'skipped') {
          $this->saveSkipped($task, (int)$subscriberId, $result['reason'] ?? 'not_found', $skippedByReason);
          $failedCount++;
        } else {
          $this->scheduledTaskSubscriberMover->moveFailedToLog($task, (int)$subscriberId, 'send_failed:' . ($result['reason'] ?? 'sending_method'));
          $failedCount++;
        }
      }

      if ($sentIds) {
        $this->scheduledTaskSubscriberMover->moveProcessedToLog($task, $sentIds);
      }
    }

    $meta = $task->getMeta() ?? [];
    $meta['sent_count'] = $sentCount;
    $meta['failed_count'] = $failedCount;
    $meta['skipped_by_reason'] = $skippedByReason;
    $task->setMeta($meta);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
    $this->saveCompletionAuditLog($task, $sentCount, $failedCount, $skippedByReason);
    return true;
  }

  /**
   * @param array<string, int> $skippedByReason
   */
  private function saveSkipped(ScheduledTaskEntity $task, int $subscriberId, string $reason, array &$skippedByReason): void {
    $skippedByReason[$reason] = ($skippedByReason[$reason] ?? 0) + 1;
    $this->scheduledTaskSubscriberMover->moveFailedToLog($task, $subscriberId, 'skipped:' . $reason);
  }

  /**
   * @param array<string, int> $skippedByReason
   */
  private function saveCompletionAuditLog(ScheduledTaskEntity $task, int $sentCount, int $failedCount, array $skippedByReason): void {
    $log = new LogEntity();
    $log->setName(LoggerFactory::TOPIC_CRON);
    $log->setLevel(self::LOG_LEVEL_INFO);
    $log->setMessage(self::AUDIT_LOG_MESSAGE_COMPLETED);
    $log->setRawMessage(self::AUDIT_LOG_MESSAGE_COMPLETED);
    $log->setContext([
      'action' => 'bulk_confirmation_email_resend_completed',
      'task_id' => $task->getId(),
      'sent_count' => $sentCount,
      'failed_count' => $failedCount,
      'skipped_by_reason' => $skippedByReason,
      'final_status' => ScheduledTaskEntity::STATUS_COMPLETED,
    ]);
    $this->logRepository->saveLog($log);
  }
}
