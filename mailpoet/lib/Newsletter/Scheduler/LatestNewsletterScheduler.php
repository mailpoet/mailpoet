<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\NewsletterReplayMetadata;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscriber;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscriberMover;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Query\Expr\Join;

class LatestNewsletterScheduler {
  public const OUTCOME_SCHEDULED = 'scheduled';
  public const OUTCOME_DUPLICATE = 'duplicate';
  public const OUTCOME_SKIPPED_NO_NEWSLETTER = 'skipped-no-newsletter';

  private EntityManager $entityManager;

  private NewslettersRepository $newslettersRepository;

  private ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository;

  private ScheduledTaskSubscriberMover $scheduledTaskSubscriberMover;

  public function __construct(
    EntityManager $entityManager,
    NewslettersRepository $newslettersRepository,
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository,
    ScheduledTaskSubscriberMover $scheduledTaskSubscriberMover
  ) {
    $this->entityManager = $entityManager;
    $this->newslettersRepository = $newslettersRepository;
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
    $this->scheduledTaskSubscriberMover = $scheduledTaskSubscriberMover;
  }

  /**
   * @param array{id:mixed,run_id:mixed,step_id:mixed,run_number:mixed} $automationMeta
   * @return array{outcome: string, newsletter: NewsletterEntity|null, task_subscriber: ScheduledTaskSubscriber|null}
   */
  public function schedule(SubscriberEntity $subscriber, int $segmentId, array $automationMeta): array {
    $source = $this->newslettersRepository->findLatestSentStandardForSegment($segmentId);
    if (!$source) {
      return [
        'outcome' => self::OUTCOME_SKIPPED_NO_NEWSLETTER,
        'newsletter' => null,
        'task_subscriber' => null,
      ];
    }

    $newsletter = $source['newsletter'];
    $subscriberId = $subscriber->getId();
    $newsletterId = $newsletter->getId();
    if (!$subscriberId || !$newsletterId) {
      throw InvalidStateException::create();
    }

    $lockName = sprintf('mailpoet_latest_replay_%d_%d', $subscriberId, $newsletterId);
    $this->acquireLock($lockName);
    try {
      return $this->entityManager->wrapInTransaction(function() use ($subscriber, $automationMeta, $source) {
        $newsletter = $source['newsletter'];
        $subscriberId = $subscriber->getId();
        $newsletterId = $newsletter->getId();
        if (!$subscriberId || !$newsletterId) {
          throw InvalidStateException::create();
        }

        if (
          $this->hasSuccessfulProcessedSend($newsletter, $subscriber)
          || $this->hasStatisticsNewsletter($newsletter, $subscriber)
          || $this->hasPendingNonReplayTaskSubscriber($newsletter, $subscriber)
        ) {
          return [
            'outcome' => self::OUTCOME_DUPLICATE,
            'newsletter' => $newsletter,
            'task_subscriber' => null,
          ];
        }

        $existingReplay = $this->findExistingReplayTaskSubscriber($newsletter, $subscriber);
        if ($existingReplay !== null) {
          $task = $existingReplay->getTask();
          $meta = $task ? $task->getMeta() : [];
          $isSameRun = ($meta[NewsletterReplayMetadata::AUTOMATION]['run_id'] ?? null) === ($automationMeta['run_id'] ?? null);
          return [
            'outcome' => $isSameRun ? self::OUTCOME_SCHEDULED : self::OUTCOME_DUPLICATE,
            'newsletter' => $newsletter,
            'task_subscriber' => $isSameRun ? $existingReplay : null,
          ];
        }

        $taskSubscriber = $this->createReplaySendingTask($source, $subscriber, $automationMeta);
        return [
          'outcome' => self::OUTCOME_SCHEDULED,
          'newsletter' => $newsletter,
          'task_subscriber' => $taskSubscriber,
        ];
      });
    } finally {
      $this->releaseLock($lockName);
    }
  }

  public function getScheduledTaskSubscriber(NewsletterEntity $newsletter, SubscriberEntity $subscriber, AutomationRun $run): ?ScheduledTaskSubscriber {
    // a finished send (sent or failed) was moved to the log
    $processed = $this->findRunReplayTaskSubscriber(ScheduledTaskSubscriberEntity::class, 'sts', $newsletter, $subscriber, $run);
    if ($processed instanceof ScheduledTaskSubscriberEntity) {
      return ScheduledTaskSubscriber::fromProcessed($processed);
    }

    // a still-pending send sits in the queue
    $queued = $this->findRunReplayTaskSubscriber(ScheduledTaskQueuedSubscriberEntity::class, 'stsq', $newsletter, $subscriber, $run);
    if ($queued instanceof ScheduledTaskQueuedSubscriberEntity) {
      return ScheduledTaskSubscriber::fromQueued($queued);
    }

    return null;
  }

  public function saveErrorAndPause(ScheduledTaskSubscriber $scheduledTaskSubscriber, string $error): void {
    $task = $scheduledTaskSubscriber->getTask();
    $subscriberId = $scheduledTaskSubscriber->getSubscriberId();
    if (!$task || !$subscriberId) {
      return;
    }
    if ($scheduledTaskSubscriber->isPending()) {
      $this->scheduledTaskSubscriberMover->moveFailedToLog($task, $subscriberId, $error);
    } else {
      $this->scheduledTaskSubscribersRepository->saveError($task, $subscriberId, $error);
    }
    $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
    $this->entityManager->flush();
  }

  /**
   * @param class-string $entityClass
   * @return ScheduledTaskSubscriberEntity|ScheduledTaskQueuedSubscriberEntity|null
   */
  private function findRunReplayTaskSubscriber(string $entityClass, string $alias, NewsletterEntity $newsletter, SubscriberEntity $subscriber, AutomationRun $run) {
    $results = $this->entityManager->createQueryBuilder()
      ->select($alias)
      ->from($entityClass, $alias)
      ->join("$alias.task", 'st')
      ->join('st.sendingQueue', 'sq')
      ->where('sq.newsletter = :newsletter')
      ->andWhere("$alias.subscriber = :subscriber")
      ->andWhere('st.createdAt >= :runCreatedAt')
      ->setParameter('newsletter', $newsletter)
      ->setParameter('subscriber', $subscriber)
      ->setParameter('runCreatedAt', $run->getCreatedAt())
      ->getQuery()
      ->getResult();

    foreach ($results as $taskSubscriber) {
      if (
        !$taskSubscriber instanceof ScheduledTaskSubscriberEntity
        && !$taskSubscriber instanceof ScheduledTaskQueuedSubscriberEntity
      ) {
        continue;
      }
      $task = $taskSubscriber->getTask();
      if (!$task instanceof ScheduledTaskEntity || !NewsletterReplayMetadata::isLatestNewsletterReplayMeta($task->getMeta())) {
        continue;
      }
      $meta = $task->getMeta();
      if (($meta[NewsletterReplayMetadata::AUTOMATION]['run_id'] ?? null) === $run->getId()) {
        return $taskSubscriber;
      }
    }
    return null;
  }

  private function hasSuccessfulProcessedSend(NewsletterEntity $newsletter, SubscriberEntity $subscriber): bool {
    $result = $this->entityManager->createQueryBuilder()
      ->select('COUNT(st)')
      ->from(ScheduledTaskSubscriberEntity::class, 'sts')
      ->join('sts.task', 'st')
      ->join(SendingQueueEntity::class, 'sq', Join::WITH, 'sq.task = st')
      ->where('sq.newsletter = :newsletter')
      ->andWhere('sts.subscriber = :subscriber')
      ->andWhere('sts.processed = :processed')
      ->andWhere('sts.failed = :notFailed')
      ->andWhere('st.status = :completed')
      ->setParameter('newsletter', $newsletter)
      ->setParameter('subscriber', $subscriber)
      ->setParameter('processed', ScheduledTaskSubscriberEntity::STATUS_PROCESSED)
      ->setParameter('notFailed', ScheduledTaskSubscriberEntity::FAIL_STATUS_OK)
      ->setParameter('completed', ScheduledTaskEntity::STATUS_COMPLETED)
      ->getQuery()
      ->getSingleScalarResult();

    return (int)$result > 0;
  }

  private function hasPendingNonReplayTaskSubscriber(NewsletterEntity $newsletter, SubscriberEntity $subscriber): bool {
    $result = $this->entityManager->createQueryBuilder()
      ->select('COUNT(st)')
      ->from(ScheduledTaskQueuedSubscriberEntity::class, 'stsq')
      ->join('stsq.task', 'st')
      ->join(SendingQueueEntity::class, 'sq', Join::WITH, 'sq.task = st')
      ->where('sq.newsletter = :newsletter')
      ->andWhere('stsq.subscriber = :subscriber')
      ->andWhere('(st.status = :scheduled OR st.status IS NULL)')
      ->andWhere('(st.meta IS NULL OR st.meta NOT LIKE :latestNewsletterReplayMeta)')
      ->andWhere('(sq.meta IS NULL OR sq.meta NOT LIKE :latestNewsletterReplayMeta)')
      ->setParameter('newsletter', $newsletter)
      ->setParameter('subscriber', $subscriber)
      ->setParameter('scheduled', ScheduledTaskEntity::STATUS_SCHEDULED)
      ->setParameter('latestNewsletterReplayMeta', NewsletterReplayMetadata::getMetaLikePattern())
      ->getQuery()
      ->getSingleScalarResult();

    return (int)$result > 0;
  }

  private function hasStatisticsNewsletter(NewsletterEntity $newsletter, SubscriberEntity $subscriber): bool {
    $result = $this->entityManager->createQueryBuilder()
      ->select('COUNT(statistics)')
      ->from(StatisticsNewsletterEntity::class, 'statistics')
      ->where('statistics.newsletter = :newsletter')
      ->andWhere('statistics.subscriber = :subscriber')
      ->setParameter('newsletter', $newsletter)
      ->setParameter('subscriber', $subscriber)
      ->getQuery()
      ->getSingleScalarResult();

    return (int)$result > 0;
  }

  private function findExistingReplayTaskSubscriber(NewsletterEntity $newsletter, SubscriberEntity $subscriber): ?ScheduledTaskSubscriber {
    // a pending replay still sits in the queue
    $queuedResults = $this->entityManager->createQueryBuilder()
      ->select('stsq')
      ->from(ScheduledTaskQueuedSubscriberEntity::class, 'stsq')
      ->join('stsq.task', 'st')
      ->join(SendingQueueEntity::class, 'sq', Join::WITH, 'sq.task = st')
      ->where('sq.newsletter = :newsletter')
      ->andWhere('stsq.subscriber = :subscriber')
      ->setParameter('newsletter', $newsletter)
      ->setParameter('subscriber', $subscriber)
      ->getQuery()
      ->getResult();

    foreach ($queuedResults as $queued) {
      if (!$queued instanceof ScheduledTaskQueuedSubscriberEntity) {
        continue;
      }
      $task = $queued->getTask();
      if (!$task instanceof ScheduledTaskEntity || !NewsletterReplayMetadata::isLatestNewsletterReplayMeta($task->getMeta())) {
        continue;
      }
      if (in_array($task->getStatus(), [ScheduledTaskEntity::STATUS_SCHEDULED, null], true)) {
        return ScheduledTaskSubscriber::fromQueued($queued);
      }
    }

    // a completed replay was moved to the log
    $processedResults = $this->entityManager->createQueryBuilder()
      ->select('sts')
      ->from(ScheduledTaskSubscriberEntity::class, 'sts')
      ->join('sts.task', 'st')
      ->join(SendingQueueEntity::class, 'sq', Join::WITH, 'sq.task = st')
      ->where('sq.newsletter = :newsletter')
      ->andWhere('sts.subscriber = :subscriber')
      ->andWhere('sts.failed = :notFailed')
      ->setParameter('newsletter', $newsletter)
      ->setParameter('subscriber', $subscriber)
      ->setParameter('notFailed', ScheduledTaskSubscriberEntity::FAIL_STATUS_OK)
      ->getQuery()
      ->getResult();

    foreach ($processedResults as $processed) {
      if (!$processed instanceof ScheduledTaskSubscriberEntity) {
        continue;
      }
      $task = $processed->getTask();
      if (!$task instanceof ScheduledTaskEntity || !NewsletterReplayMetadata::isLatestNewsletterReplayMeta($task->getMeta())) {
        continue;
      }
      if (
        $task->getStatus() === ScheduledTaskEntity::STATUS_COMPLETED
        && $processed->getProcessed() === ScheduledTaskSubscriberEntity::STATUS_PROCESSED
      ) {
        return ScheduledTaskSubscriber::fromProcessed($processed);
      }
    }

    return null;
  }

  /**
   * @param array{newsletter: NewsletterEntity, queue: SendingQueueEntity, task: ScheduledTaskEntity} $source
   */
  private function createReplaySendingTask(array $source, SubscriberEntity $subscriber, array $automationMeta): ScheduledTaskSubscriber {
    $sourceTask = $source['task'];
    $sourceQueue = $source['queue'];
    $newsletter = $source['newsletter'];

    $meta = [
      NewsletterReplayMetadata::LATEST_NEWSLETTER_REPLAY => true,
      NewsletterReplayMetadata::REPLAY_SOURCE_NEWSLETTER_ID => $newsletter->getId(),
      NewsletterReplayMetadata::REPLAY_SOURCE_QUEUE_ID => $sourceQueue->getId(),
      NewsletterReplayMetadata::REPLAY_SOURCE_TASK_ID => $sourceTask->getId(),
      NewsletterReplayMetadata::REPLAY_SUBSCRIBER_ID => $subscriber->getId(),
      NewsletterReplayMetadata::AUTOMATION => $automationMeta,
    ];

    $task = new ScheduledTaskEntity();
    $task->setType(SendingQueue::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setScheduledAt(Carbon::now()->millisecond(0));
    $task->setPriority(ScheduledTaskEntity::PRIORITY_MEDIUM);
    $task->setMeta($meta);
    $this->entityManager->persist($task);

    $taskSubscriber = new ScheduledTaskQueuedSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($taskSubscriber);

    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $task->setSendingQueue($queue);
    $queue->setMeta($meta);
    $queue->setNewsletter($newsletter);
    $queue->setCountToProcess(1);
    $queue->setCountTotal(1);
    $this->entityManager->persist($queue);

    return ScheduledTaskSubscriber::fromQueued($taskSubscriber);
  }

  private function acquireLock(string $lockName): void {
    $result = $this->entityManager->getConnection()->executeQuery(
      'SELECT GET_LOCK(:lockName, 10)',
      ['lockName' => $lockName]
    )->fetchOne();
    if (!is_numeric($result) || (int)$result !== 1) {
      throw InvalidStateException::create()->withMessage(__('Could not create sending task.', 'mailpoet'));
    }
  }

  private function releaseLock(string $lockName): void {
    $this->entityManager->getConnection()->executeQuery(
      'SELECT RELEASE_LOCK(:lockName)',
      ['lockName' => $lockName]
    );
  }
}
