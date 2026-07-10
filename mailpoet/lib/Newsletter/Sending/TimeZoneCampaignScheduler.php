<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class TimeZoneCampaignScheduler {
  public const SCHEDULE_MODE_WEBSITE_TIME = 'website_time';
  public const SCHEDULE_MODE_SUBSCRIBER_TIMEZONE = 'subscriber_timezone';
  public const META_SEND_BY_TIMEZONE = 'sendByTimezone';
  public const META_TIMEZONE_CAMPAIGN_ID = 'timezoneCampaignId';
  public const META_SELECTED_LOCAL_DATE = 'selectedLocalDate';
  public const META_SELECTED_LOCAL_TIME = 'selectedLocalTime';
  public const META_GROUP_TIMEZONE = 'groupTimezone';
  public const META_FALLBACK_USED = 'fallbackUsed';
  public const META_SITE_TIMEZONE = 'siteTimezone';
  public const META_FIRST_SCHEDULED_AT = 'firstScheduledAt';
  public const META_LAST_SCHEDULED_AT = 'lastScheduledAt';
  public const META_NEXT_SCHEDULED_AT = 'nextScheduledAt';
  public const META_TIMEZONE_BREAKDOWN = 'timezoneBreakdown';
  private const LEAD_TIME_HOURS = 24;

  private CapabilitiesManager $capabilitiesManager;
  private EntityManager $entityManager;
  private FeaturesController $featuresController;
  private ScheduledTasksRepository $scheduledTasksRepository;
  private ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository;
  private SendingQueuesRepository $sendingQueuesRepository;
  private SubscribersFinder $subscribersFinder;
  private SubscribersRepository $subscribersRepository;
  private WPFunctions $wp;

  public function __construct(
    CapabilitiesManager $capabilitiesManager,
    EntityManager $entityManager,
    FeaturesController $featuresController,
    ScheduledTasksRepository $scheduledTasksRepository,
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    SubscribersFinder $subscribersFinder,
    SubscribersRepository $subscribersRepository,
    WPFunctions $wp
  ) {
    $this->capabilitiesManager = $capabilitiesManager;
    $this->entityManager = $entityManager;
    $this->featuresController = $featuresController;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->subscribersFinder = $subscribersFinder;
    $this->subscribersRepository = $subscribersRepository;
    $this->wp = $wp;
  }

  public function isSubscriberTimeZoneMode(NewsletterEntity $newsletter): bool {
    return $newsletter->getType() === NewsletterEntity::TYPE_STANDARD
      && $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_SCHEDULE_MODE) === self::SCHEDULE_MODE_SUBSCRIBER_TIMEZONE;
  }

  /**
   * @throws \Exception
   */
  public function schedule(NewsletterEntity $newsletter): SendingQueueEntity {
    if (!$this->featuresController->isSupported(FeaturesController::FEATURE_SEND_BY_TIMEZONE)) {
      throw new \Exception(__('Send by subscriber time zone is not available.', 'mailpoet'), 400);
    }
    if (!$this->isSubscriberTimeZoneMode($newsletter)) {
      throw new \Exception(__('Send by subscriber time zone is available only for standard newsletters.', 'mailpoet'), 400);
    }
    $capability = $this->capabilitiesManager->getCapability('sendByTimezone');
    if ($capability && $capability->isRestricted) {
      throw new \Exception(__('Send by subscriber time zone requires a paid MailPoet plan.', 'mailpoet'), 403);
    }
    if (!$this->canReplaceScheduledQueues($newsletter)) {
      throw new \Exception(__('This email can no longer be edited because one or more time zone batches have already started.', 'mailpoet'), 400);
    }

    $selectedLocalDate = $this->getRequiredOption($newsletter, NewsletterOptionFieldEntity::NAME_SCHEDULED_LOCAL_DATE);
    $selectedLocalTime = $this->normalizeLocalTime($this->getRequiredOption($newsletter, NewsletterOptionFieldEntity::NAME_SCHEDULED_LOCAL_TIME));
    $this->validateLocalDate($selectedLocalDate);
    $this->validateLocalTime($selectedLocalTime);

    $subscriberIds = $this->subscribersFinder->getSubscriberIdsFromSegments($newsletter->getSegmentIds(), $newsletter->getFilterSegmentId());
    if ($subscriberIds === []) {
      throw new \Exception(__('There are no subscribers in that list!', 'mailpoet'));
    }

    $siteTimeZone = $this->wp->wpTimezone();
    $siteTimeZoneName = $siteTimeZone->getName();
    $groups = $this->groupSubscribersByTimeZone($subscriberIds, $siteTimeZoneName);
    if ($groups === []) {
      throw new \Exception(__('There are no subscribers in that list!', 'mailpoet'));
    }

    $schedule = $this->buildGroupSchedule($groups, $selectedLocalDate, $selectedLocalTime);
    $this->validateLeadTime($schedule);

    $campaignId = Security::generateRandomString(16);
    $firstScheduledAt = $schedule[0]['scheduledAt']->format('Y-m-d H:i:s');
    $lastScheduledAt = $schedule[count($schedule) - 1]['scheduledAt']->format('Y-m-d H:i:s');

    $this->entityManager->beginTransaction();
    try {
      $this->deleteReplaceableScheduledQueues($newsletter);
      $createdQueues = [];

      foreach ($schedule as $group) {
        $task = new ScheduledTaskEntity();
        $task->setType(SendingQueueWorker::TASK_TYPE);
        $task->setPriority(ScheduledTaskEntity::PRIORITY_MEDIUM);
        $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
        $task->setScheduledAt($group['scheduledAt']);

        $queue = new SendingQueueEntity();
        $queue->setNewsletter($newsletter);
        $queue->setTask($task);
        $queue->setMeta([
          self::META_SEND_BY_TIMEZONE => true,
          self::META_TIMEZONE_CAMPAIGN_ID => $campaignId,
          self::META_SELECTED_LOCAL_DATE => $selectedLocalDate,
          self::META_SELECTED_LOCAL_TIME => $selectedLocalTime,
          self::META_GROUP_TIMEZONE => $group['timeZone'],
          self::META_FALLBACK_USED => $group['fallbackUsed'],
          self::META_SITE_TIMEZONE => $siteTimeZoneName,
          self::META_FIRST_SCHEDULED_AT => $firstScheduledAt,
          self::META_LAST_SCHEDULED_AT => $lastScheduledAt,
        ]);

        $this->scheduledTasksRepository->persist($task);
        $this->sendingQueuesRepository->persist($queue);
        $this->entityManager->flush();

        $this->scheduledTaskSubscribersRepository->addSubscribersByIds($task, $group['subscriberIds']);
        $this->sendingQueuesRepository->updateCounts($queue);
        $createdQueues[] = $queue;
      }

      $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);
      $this->entityManager->flush();
      $this->entityManager->commit();
    } catch (\Throwable $exception) {
      $this->entityManager->rollback();
      throw $exception;
    }

    return $createdQueues[0];
  }

  public function isTimeZoneQueue(SendingQueueEntity $queue): bool {
    $meta = $queue->getMeta() ?? [];
    return !empty($meta[self::META_SEND_BY_TIMEZONE]) && !empty($meta[self::META_TIMEZONE_CAMPAIGN_ID]);
  }

  public function getCampaignId(SendingQueueEntity $queue): ?string {
    if (!$this->isTimeZoneQueue($queue)) {
      return null;
    }
    $meta = $queue->getMeta() ?? [];
    return is_string($meta[self::META_TIMEZONE_CAMPAIGN_ID] ?? null) ? $meta[self::META_TIMEZONE_CAMPAIGN_ID] : null;
  }

  /** @return SendingQueueEntity[] */
  public function getCampaignQueues(SendingQueueEntity $queue): array {
    $campaignId = $this->getCampaignId($queue);
    $newsletter = $queue->getNewsletter();
    if (!$campaignId || !$newsletter instanceof NewsletterEntity) {
      return [$queue];
    }
    return $this->getCampaignQueuesById($newsletter, $campaignId);
  }

  /** Resolves replay sources directly so newsletters with many replays stay cheap to inspect. */
  public function resolveTimeZoneCampaignQueue(NewsletterEntity $newsletter): ?SendingQueueEntity {
    $latestQueue = $newsletter->getLatestQueue();
    if ($latestQueue === null) {
      return null;
    }
    if ($this->isTimeZoneQueue($latestQueue)) {
      return $latestQueue;
    }

    $meta = $latestQueue->getMeta() ?? [];
    if (!NewsletterReplayMetadata::isLatestNewsletterReplayMeta($meta)) {
      return null;
    }
    $sourceQueueId = (int)($meta[NewsletterReplayMetadata::REPLAY_SOURCE_QUEUE_ID] ?? 0);
    if ($sourceQueueId <= 0) {
      return null;
    }

    $sourceQueue = $this->sendingQueuesRepository->findOneBy([
      'id' => $sourceQueueId,
      'newsletter' => $newsletter,
    ]);
    return $sourceQueue instanceof SendingQueueEntity && $this->isTimeZoneQueue($sourceQueue)
      ? $sourceQueue
      : null;
  }

  public function hasIncompleteCampaignQueues(SendingQueueEntity $queue): bool {
    foreach ($this->getCampaignQueues($queue) as $campaignQueue) {
      $task = $campaignQueue->getTask();
      if (!$task instanceof ScheduledTaskEntity) {
        continue;
      }
      if (
        !in_array(
          $task->getStatus(),
          [
            ScheduledTaskEntity::STATUS_COMPLETED,
            ScheduledTaskEntity::STATUS_CANCELLED,
            ScheduledTaskEntity::STATUS_INVALID,
          ],
          true
        )
      ) {
        return true;
      }
    }
    return false;
  }

  public function pauseCampaign(SendingQueueEntity $queue): void {
    foreach ($this->getCampaignQueues($queue) as $campaignQueue) {
      // Mirrors the guard in resumeCampaign(): a queue is only "fully processed"
      // when it has work to do AND all of it has been done. A zero-count queue
      // is still pending and must be paused.
      if ($campaignQueue->getCountTotal() > 0 && $campaignQueue->getCountProcessed() === $campaignQueue->getCountTotal()) {
        continue;
      }
      $task = $campaignQueue->getTask();
      if ($task instanceof ScheduledTaskEntity) {
        $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
      }
    }
    $this->entityManager->flush();
  }

  public function resumeCampaign(SendingQueueEntity $queue): void {
    $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    $newsletter = $queue->getNewsletter();
    $hasDueBatch = false;
    $hasPendingBatch = false;
    foreach ($this->getCampaignQueues($queue) as $campaignQueue) {
      $task = $campaignQueue->getTask();
      if (!$task instanceof ScheduledTaskEntity) {
        continue;
      }
      if ($campaignQueue->getCountProcessed() === $campaignQueue->getCountTotal() && $campaignQueue->getCountTotal() > 0) {
        // Mirrors SendingQueuesRepository::resume(): when pause interrupted the worker after all
        // recipients were processed but before STATUS_COMPLETED was set, finalize processedAt now
        // so aggregate "processed at" reporting is not left null for this batch.
        $task->setProcessedAt(Carbon::now()->millisecond(0));
        $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
        continue;
      }
      $hasPendingBatch = true;
      $scheduledAt = $task->getScheduledAt();
      if ($scheduledAt && $scheduledAt > $now) {
        $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
      } else {
        $task->setStatus(null);
        $hasDueBatch = true;
      }
    }

    if ($newsletter instanceof NewsletterEntity) {
      if (!$hasPendingBatch) {
        // All batches are already completed: do not demote a finished campaign back to
        // SCHEDULED. No task remains for the scheduler to pick up, so doing so would
        // leave the newsletter permanently stuck in the wrong status.
        if ($newsletter->canBeSetSent()) {
          $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
        }
      } else {
        $newsletter->setStatus($hasDueBatch ? NewsletterEntity::STATUS_SENDING : NewsletterEntity::STATUS_SCHEDULED);
      }
    }
    $this->entityManager->flush();
  }

  /**
   * @return array{status:?string,scheduledAt:\DateTimeInterface|null,processedAt:\DateTimeInterface|null,countTotal:int,countProcessed:int,countToProcess:int,meta:array<string,mixed>}|null
   */
  public function getAggregateQueueData(SendingQueueEntity $queue): ?array {
    if (!$this->isTimeZoneQueue($queue)) {
      return null;
    }

    $queues = $this->getCampaignQueues($queue);
    $countTotal = 0;
    $countProcessed = 0;
    $countToProcess = 0;
    $firstScheduledAt = null;
    $lastScheduledAt = null;
    $nextScheduledAt = null;
    $lastProcessedAt = null;
    $breakdown = [];
    $statuses = [];
    $meta = $queue->getMeta() ?? [];

    foreach ($queues as $campaignQueue) {
      $task = $campaignQueue->getTask();
      if (!$task instanceof ScheduledTaskEntity) {
        continue;
      }
      $countTotal += $campaignQueue->getCountTotal();
      $countProcessed += $campaignQueue->getCountProcessed();
      $countToProcess += $campaignQueue->getCountToProcess();
      $statuses[] = $task->getStatus();
      $scheduledAt = $task->getScheduledAt();
      if ($scheduledAt) {
        $firstScheduledAt = $this->minDate($firstScheduledAt, $scheduledAt);
        $lastScheduledAt = $this->maxDate($lastScheduledAt, $scheduledAt);
        if ($task->getStatus() === ScheduledTaskEntity::STATUS_SCHEDULED || $task->getStatus() === ScheduledTaskEntity::STATUS_PAUSED) {
          $nextScheduledAt = $this->minDate($nextScheduledAt, $scheduledAt);
        }
      }
      $processedAt = $task->getProcessedAt();
      if ($processedAt) {
        $lastProcessedAt = $this->maxDate($lastProcessedAt, $processedAt);
      }
      $queueMeta = $campaignQueue->getMeta() ?? [];
      $breakdown[] = [
        'timezone' => $queueMeta[self::META_GROUP_TIMEZONE] ?? null,
        'fallback_used' => $queueMeta[self::META_FALLBACK_USED] ?? false,
        'scheduled_at' => $scheduledAt ? $scheduledAt->format('Y-m-d H:i:s') : null,
        'status' => $task->getStatus(),
        'in_progress' => (bool)$task->getInProgress(),
        'count_total' => $campaignQueue->getCountTotal(),
        'count_processed' => $campaignQueue->getCountProcessed(),
        'count_to_process' => $campaignQueue->getCountToProcess(),
      ];
    }

    $meta[self::META_FIRST_SCHEDULED_AT] = $firstScheduledAt ? $firstScheduledAt->format('Y-m-d H:i:s') : null;
    $meta[self::META_LAST_SCHEDULED_AT] = $lastScheduledAt ? $lastScheduledAt->format('Y-m-d H:i:s') : null;
    $meta[self::META_NEXT_SCHEDULED_AT] = $nextScheduledAt ? $nextScheduledAt->format('Y-m-d H:i:s') : null;
    $meta[self::META_TIMEZONE_BREAKDOWN] = $breakdown;

    return [
      'status' => $this->resolveAggregateStatus($statuses),
      'scheduledAt' => $nextScheduledAt ?: $firstScheduledAt,
      'processedAt' => $lastProcessedAt,
      'countTotal' => $countTotal,
      'countProcessed' => $countProcessed,
      'countToProcess' => $countToProcess,
      'meta' => $meta,
    ];
  }

  private function getRequiredOption(NewsletterEntity $newsletter, string $optionName): string {
    $value = $newsletter->getOptionValue($optionName);
    return is_string($value) ? $value : '';
  }

  private function normalizeLocalTime(string $time): string {
    if (strlen($time) === 5) {
      return "{$time}:00";
    }
    return $time;
  }

  /**
   * @throws \Exception
   */
  private function validateLocalDate(string $date): void {
    $dateTime = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$dateTime || $dateTime->format('Y-m-d') !== $date) {
      throw new \Exception(__('Please enter a valid scheduled date.', 'mailpoet'), 400);
    }
  }

  /**
   * @throws \Exception
   */
  private function validateLocalTime(string $time): void {
    $dateTime = \DateTimeImmutable::createFromFormat('!H:i:s', $time);
    if (
      !$dateTime
      || $dateTime->format('H:i:s') !== $time
      || ((int)$dateTime->format('i')) % 15 !== 0
      || ((int)$dateTime->format('s')) !== 0
    ) {
      throw new \Exception(__('Please enter a valid scheduled time.', 'mailpoet'), 400);
    }
  }

  /**
   * @param int[] $subscriberIds
   * @return array<string,array{timeZone:string,fallbackUsed:bool,subscriberIds:int[]}>
   */
  private function groupSubscribersByTimeZone(array $subscriberIds, string $siteTimeZoneName): array {
    $groups = [];
    foreach (array_chunk($subscriberIds, 1000) as $idsChunk) {
      $subscribers = $this->subscribersRepository->findBy(['id' => $idsChunk]);
      foreach ($subscribers as $subscriber) {
        if (!$subscriber instanceof SubscriberEntity || !$subscriber->getId()) {
          continue;
        }
        $timeZone = SubscriberEntity::sanitizeTimeZone($subscriber->getTimeZone());
        $fallbackUsed = $timeZone === null;
        $resolvedTimeZone = $timeZone ?: $siteTimeZoneName;
        $key = $resolvedTimeZone . ':' . (int)$fallbackUsed;
        if (!isset($groups[$key])) {
          $groups[$key] = [
            'timeZone' => $resolvedTimeZone,
            'fallbackUsed' => $fallbackUsed,
            'subscriberIds' => [],
          ];
        }
        $groups[$key]['subscriberIds'][] = (int)$subscriber->getId();
      }
    }
    return $groups;
  }

  /**
   * @param array<string,array{timeZone:string,fallbackUsed:bool,subscriberIds:int[]}> $groups
   * @return array<int,array{timeZone:string,fallbackUsed:bool,subscriberIds:int[],scheduledAt:\DateTimeImmutable}>
   */
  private function buildGroupSchedule(array $groups, string $selectedLocalDate, string $selectedLocalTime): array {
    $schedule = [];
    foreach ($groups as $group) {
      $scheduledAt = new \DateTimeImmutable(
        "{$selectedLocalDate} {$selectedLocalTime}",
        new \DateTimeZone($group['timeZone'])
      );
      $scheduledAt = $scheduledAt->setTimezone(new \DateTimeZone('UTC'));
      $schedule[] = [
        'timeZone' => $group['timeZone'],
        'fallbackUsed' => $group['fallbackUsed'],
        'subscriberIds' => $group['subscriberIds'],
        'scheduledAt' => $scheduledAt,
      ];
    }
    usort($schedule, function(array $a, array $b): int {
      return $a['scheduledAt'] <=> $b['scheduledAt'];
    });
    return $schedule;
  }

  /**
   * @param array<int,array{timeZone:string,fallbackUsed:bool,subscriberIds:int[],scheduledAt:\DateTimeImmutable}> $schedule
   * @throws \Exception
   */
  private function validateLeadTime(array $schedule): void {
    $earliest = $schedule[0]['scheduledAt'];
    $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    if ($earliest <= $now) {
      throw new \Exception(__('Subscriber time zone scheduling cannot include time zones that have already passed.', 'mailpoet'), 400);
    }
    if ($earliest < $now->modify('+' . self::LEAD_TIME_HOURS . ' hours')) {
      throw new \Exception(sprintf(
        // translators: %d is the minimum number of hours required before the first timezone batch can send.
        __('Subscriber time zone scheduling requires at least %d hours of lead time.', 'mailpoet'),
        self::LEAD_TIME_HOURS
      ), 400);
    }
  }

  public function canReplaceScheduledCampaign(NewsletterEntity $newsletter): bool {
    foreach ($this->getTimeZoneQueuesForNewsletter($newsletter) as $queue) {
      if (!$this->isReplaceableScheduledQueue($queue)) {
        return false;
      }
    }
    return true;
  }

  public function canReplaceScheduledQueues(NewsletterEntity $newsletter): bool {
    foreach ($this->getQueuesForNewsletter($newsletter) as $queue) {
      if ($this->isTerminalQueue($queue)) {
        continue;
      }
      if (!$this->isReplaceableScheduledQueue($queue)) {
        return false;
      }
    }
    return true;
  }

  public function deleteReplaceableScheduledQueues(NewsletterEntity $newsletter): void {
    foreach ($this->getQueuesForNewsletter($newsletter) as $queue) {
      if ($this->isReplaceableScheduledQueue($queue)) {
        $this->deleteQueue($queue);
      }
    }
    $this->entityManager->flush();
  }

  public function deleteScheduledCampaignQueues(NewsletterEntity $newsletter): void {
    foreach ($this->getTimeZoneQueuesForNewsletter($newsletter) as $queue) {
      $this->deleteQueue($queue);
    }
    $this->entityManager->flush();
  }

  /** @return SendingQueueEntity[] */
  private function getQueuesForNewsletter(NewsletterEntity $newsletter): array {
    return $this->sendingQueuesRepository->findBy(['newsletter' => $newsletter]);
  }

  /** @return SendingQueueEntity[] */
  private function getTimeZoneQueuesForNewsletter(NewsletterEntity $newsletter): array {
    return array_values(array_filter($this->getQueuesForNewsletter($newsletter), function(SendingQueueEntity $queue): bool {
      return $this->isTimeZoneQueue($queue);
    }));
  }

  private function isReplaceableScheduledQueue(SendingQueueEntity $queue): bool {
    $task = $queue->getTask();
    return $task instanceof ScheduledTaskEntity
      && $queue->getCountProcessed() === 0
      && !$task->getInProgress()
      && in_array($task->getStatus(), [ScheduledTaskEntity::STATUS_SCHEDULED, ScheduledTaskEntity::STATUS_PAUSED], true);
  }

  private function isTerminalQueue(SendingQueueEntity $queue): bool {
    $task = $queue->getTask();
    return $task instanceof ScheduledTaskEntity
      && in_array(
        $task->getStatus(),
        [
          ScheduledTaskEntity::STATUS_COMPLETED,
          ScheduledTaskEntity::STATUS_CANCELLED,
          ScheduledTaskEntity::STATUS_INVALID,
        ],
        true
      );
  }

  private function deleteQueue(SendingQueueEntity $queue): void {
    $task = $queue->getTask();
    if ($task instanceof ScheduledTaskEntity) {
      $this->scheduledTaskSubscribersRepository->deleteByScheduledTask($task);
    }
    $this->sendingQueuesRepository->remove($queue);
    if ($task instanceof ScheduledTaskEntity) {
      $this->scheduledTasksRepository->remove($task);
    }
  }

  /** @return SendingQueueEntity[] */
  private function getCampaignQueuesById(NewsletterEntity $newsletter, string $campaignId): array {
    $queues = array_filter($this->getTimeZoneQueuesForNewsletter($newsletter), function(SendingQueueEntity $queue) use ($campaignId): bool {
      return $this->getCampaignId($queue) === $campaignId;
    });
    usort($queues, function(SendingQueueEntity $a, SendingQueueEntity $b): int {
      $taskA = $a->getTask();
      $taskB = $b->getTask();
      $scheduledA = $taskA instanceof ScheduledTaskEntity ? $taskA->getScheduledAt() : null;
      $scheduledB = $taskB instanceof ScheduledTaskEntity ? $taskB->getScheduledAt() : null;
      if (!$scheduledA || !$scheduledB) {
        return (int)$a->getId() <=> (int)$b->getId();
      }
      return $scheduledA <=> $scheduledB;
    });
    return $queues;
  }

  /**
   * Resolves the aggregate status of a multi-batch time zone campaign from the per-batch statuses.
   *
   * The priority is intentionally explicit (instead of falling back to "the first status in the
   * list") so that the result is deterministic and reflects what the campaign is doing, not the
   * order in which batches happen to be sorted.
   */
  private function resolveAggregateStatus(array $statuses): ?string {
    if ($statuses === []) {
      return null;
    }
    if (in_array(ScheduledTaskEntity::STATUS_PAUSED, $statuses, true)) {
      return ScheduledTaskEntity::STATUS_PAUSED;
    }
    if (in_array(null, $statuses, true)) {
      return null;
    }
    if (in_array(ScheduledTaskEntity::STATUS_SCHEDULED, $statuses, true)) {
      return ScheduledTaskEntity::STATUS_SCHEDULED;
    }
    if (in_array(ScheduledTaskEntity::STATUS_COMPLETED, $statuses, true)) {
      return ScheduledTaskEntity::STATUS_COMPLETED;
    }
    if (in_array(ScheduledTaskEntity::STATUS_CANCELLED, $statuses, true)) {
      return ScheduledTaskEntity::STATUS_CANCELLED;
    }
    return ScheduledTaskEntity::STATUS_INVALID;
  }

  private function minDate(?\DateTimeInterface $current, \DateTimeInterface $candidate): \DateTimeInterface {
    return $current && $current <= $candidate ? $current : $candidate;
  }

  private function maxDate(?\DateTimeInterface $current, \DateTimeInterface $candidate): \DateTimeInterface {
    return $current && $current >= $candidate ? $current : $candidate;
  }
}
