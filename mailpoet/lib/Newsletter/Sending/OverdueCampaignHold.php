<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class OverdueCampaignHold {
  public const HELD_TYPES = [
    NewsletterEntity::TYPE_STANDARD,
    NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
    NewsletterEntity::TYPE_RE_ENGAGEMENT,
  ];

  private const DEFAULT_THRESHOLD = 86400; // 24 hours in seconds

  /** @var WPFunctions */
  private $wp;

  /** @var TimeZoneCampaignScheduler */
  private $timeZoneCampaignScheduler;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var LoggerFactory */
  private $loggerFactory;

  public function __construct(
    WPFunctions $wp,
    TimeZoneCampaignScheduler $timeZoneCampaignScheduler,
    ScheduledTasksRepository $scheduledTasksRepository,
    LoggerFactory $loggerFactory
  ) {
    $this->wp = $wp;
    $this->timeZoneCampaignScheduler = $timeZoneCampaignScheduler;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->loggerFactory = $loggerFactory;
  }

  public function isEnabled(): bool {
    return (bool)$this->wp->applyFilters('mailpoet_hold_overdue_campaigns', true);
  }

  /**
   * Seconds a campaign may be late before it is held. Zero or less turns the hold off.
   */
  public function getThreshold(): int {
    $threshold = $this->wp->applyFilters('mailpoet_overdue_campaign_threshold', self::DEFAULT_THRESHOLD);
    return is_numeric($threshold) ? max(0, (int)$threshold) : 0;
  }

  public static function isOverdue(
    string $newsletterType,
    ?\DateTimeInterface $referenceTime,
    int $countProcessed,
    bool $confirmed,
    bool $isReplay,
    \DateTimeInterface $now,
    int $threshold
  ): bool {
    if (
      $threshold <= 0
      || $referenceTime === null
      || $countProcessed > 0
      || $confirmed
      || $isReplay
      || !in_array($newsletterType, self::HELD_TYPES, true)
    ) {
      return false;
    }
    return $now->getTimestamp() - $referenceTime->getTimestamp() > $threshold;
  }

  public function getReferenceTime(ScheduledTaskEntity $task): ?\DateTimeInterface {
    return $task->getScheduledAt() ?? $task->getCreatedAt();
  }

  /**
   * Same clock as the Scheduler uses to find due tasks.
   */
  public function now(): \DateTimeInterface {
    return Carbon::now()->millisecond(0);
  }

  /**
   * Whether a running task must be held instead of sent. It does not look at in_progress:
   * the worker marks the task in progress before it asks.
   * A time zone campaign is judged as a whole: it is held when nothing has been sent for any
   * batch and any batch that is waiting or running is overdue.
   */
  public function shouldHold(ScheduledTaskEntity $task, NewsletterEntity $newsletter): bool {
    $queue = $task->getSendingQueue();
    if (
      !$queue instanceof SendingQueueEntity
      || $task->getType() !== SendingQueue::TASK_TYPE
      || $task->getStatus() !== null
      || $newsletter->getDeletedAt() !== null
      || $task->isOverdueSendConfirmed()
      || !$this->isEnabled()
    ) {
      return false;
    }

    $queues = $this->timeZoneCampaignScheduler->isTimeZoneQueue($queue)
      ? $this->timeZoneCampaignScheduler->getCampaignQueues($queue)
      : [$queue];
    foreach ($queues as $campaignQueue) {
      $campaignTask = $campaignQueue->getTask();
      if (
        $campaignQueue->getCountProcessed() > 0
        || ($campaignTask instanceof ScheduledTaskEntity && $campaignTask->getStatus() === ScheduledTaskEntity::STATUS_COMPLETED)
      ) {
        return false;
      }
    }

    $now = $this->now();
    $threshold = $this->getThreshold();
    foreach ($queues as $campaignQueue) {
      $campaignTask = $campaignQueue->getTask();
      if (
        $campaignTask instanceof ScheduledTaskEntity
        && in_array($campaignTask->getStatus(), [null, ScheduledTaskEntity::STATUS_SCHEDULED], true)
        && self::isOverdue(
          (string)$newsletter->getType(),
          $this->getReferenceTime($campaignTask),
          $campaignQueue->getCountProcessed(),
          $campaignTask->isOverdueSendConfirmed(),
          NewsletterReplayMetadata::isLatestNewsletterReplayMeta($campaignQueue->getMeta())
            || NewsletterReplayMetadata::isLatestNewsletterReplayMeta($campaignTask->getMeta()),
          $now,
          $threshold
        )
      ) {
        return true;
      }
    }
    return false;
  }

  /**
   * Holds a task the worker owns. Time zone campaigns are paused as a whole.
   */
  public function holdInWorker(ScheduledTaskEntity $task): void {
    $queue = $task->getSendingQueue();
    if ($queue instanceof SendingQueueEntity && $this->timeZoneCampaignScheduler->isTimeZoneQueue($queue)) {
      $this->timeZoneCampaignScheduler->pauseCampaign($queue, ScheduledTaskEntity::PAUSE_REASON_OVERDUE);
    } else {
      $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
      $task->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_OVERDUE);
      $this->scheduledTasksRepository->flush();
    }

    $referenceTime = $this->getReferenceTime($task);
    $newsletter = $queue instanceof SendingQueueEntity ? $queue->getNewsletter() : null;
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
      'held overdue campaign',
      [
        'task_id' => $task->getId(),
        'newsletter_id' => $newsletter ? $newsletter->getId() : null,
        'scheduled_at' => $referenceTime ? $referenceTime->format('Y-m-d H:i:s') : null,
        'seconds_late' => $referenceTime ? $this->now()->getTimestamp() - $referenceTime->getTimestamp() : null,
      ]
    );
  }
}
