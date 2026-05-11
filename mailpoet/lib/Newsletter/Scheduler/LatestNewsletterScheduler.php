<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\NewsletterReplayMetadata;
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

  public function __construct(
    EntityManager $entityManager,
    NewslettersRepository $newslettersRepository,
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository
  ) {
    $this->entityManager = $entityManager;
    $this->newslettersRepository = $newslettersRepository;
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
  }

  /**
   * @param array{id:mixed,run_id:mixed,step_id:mixed,run_number:mixed} $automationMeta
   * @return array{outcome: string, newsletter: NewsletterEntity|null, task_subscriber: ScheduledTaskSubscriberEntity|null}
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
      return $this->entityManager->wrapInTransaction(function() use ($subscriber, $segmentId, $automationMeta, $source) {
        $newsletter = $source['newsletter'];
        $subscriberId = $subscriber->getId();
        $newsletterId = $newsletter->getId();
        if (!$subscriberId || !$newsletterId) {
          throw InvalidStateException::create();
        }

        if (
          $this->hasSuccessfulProcessedSend($newsletter, $subscriber)
          || $this->hasStatisticsNewsletter($newsletter, $subscriber)
        ) {
          return [
            'outcome' => self::OUTCOME_DUPLICATE,
            'newsletter' => $newsletter,
            'task_subscriber' => null,
          ];
        }

        $existingReplay = $this->findExistingReplayTaskSubscriber($newsletter, $subscriber);
        if ($existingReplay instanceof ScheduledTaskSubscriberEntity) {
          $task = $existingReplay->getTask();
          $meta = $task ? $task->getMeta() : [];
          $isSameRun = ($meta[NewsletterReplayMetadata::AUTOMATION]['run_id'] ?? null) === ($automationMeta['run_id'] ?? null);
          return [
            'outcome' => $isSameRun ? self::OUTCOME_SCHEDULED : self::OUTCOME_DUPLICATE,
            'newsletter' => $newsletter,
            'task_subscriber' => $isSameRun ? $existingReplay : null,
          ];
        }

        $taskSubscriber = $this->createReplaySendingTask($source, $subscriber, $segmentId, $automationMeta);
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

  public function getScheduledTaskSubscriber(NewsletterEntity $newsletter, SubscriberEntity $subscriber, AutomationRun $run): ?ScheduledTaskSubscriberEntity {
    $results = $this->entityManager->createQueryBuilder()
      ->select('sts')
      ->from(ScheduledTaskSubscriberEntity::class, 'sts')
      ->join('sts.task', 'st')
      ->join('st.sendingQueue', 'sq')
      ->where('sq.newsletter = :newsletter')
      ->andWhere('sts.subscriber = :subscriber')
      ->andWhere('st.createdAt >= :runCreatedAt')
      ->setParameter('newsletter', $newsletter)
      ->setParameter('subscriber', $subscriber)
      ->setParameter('runCreatedAt', $run->getCreatedAt())
      ->getQuery()
      ->getResult();

    foreach ($results as $scheduledTaskSubscriber) {
      if (!$scheduledTaskSubscriber instanceof ScheduledTaskSubscriberEntity) {
        continue;
      }
      $task = $scheduledTaskSubscriber->getTask();
      if (!$task instanceof ScheduledTaskEntity || !NewsletterReplayMetadata::isLatestNewsletterReplayMeta($task->getMeta())) {
        continue;
      }
      $meta = $task->getMeta();
      if (($meta[NewsletterReplayMetadata::AUTOMATION]['run_id'] ?? null) === $run->getId()) {
        return $scheduledTaskSubscriber;
      }
    }
    return null;
  }

  public function saveError(ScheduledTaskSubscriberEntity $scheduledTaskSubscriber, string $error): void {
    $task = $scheduledTaskSubscriber->getTask();
    $subscriber = $scheduledTaskSubscriber->getSubscriber();
    if (!$task || !$subscriber || !$subscriber->getId()) {
      return;
    }
    $this->scheduledTaskSubscribersRepository->saveError($task, $subscriber->getId(), $error);
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

  private function findExistingReplayTaskSubscriber(NewsletterEntity $newsletter, SubscriberEntity $subscriber): ?ScheduledTaskSubscriberEntity {
    $results = $this->entityManager->createQueryBuilder()
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

    foreach ($results as $scheduledTaskSubscriber) {
      if (!$scheduledTaskSubscriber instanceof ScheduledTaskSubscriberEntity) {
        continue;
      }
      $task = $scheduledTaskSubscriber->getTask();
      if (!$task instanceof ScheduledTaskEntity || !NewsletterReplayMetadata::isLatestNewsletterReplayMeta($task->getMeta())) {
        continue;
      }
      $status = $task->getStatus();
      if (in_array($status, [ScheduledTaskEntity::STATUS_SCHEDULED, null], true)) {
        return $scheduledTaskSubscriber;
      }
      if (
        $status === ScheduledTaskEntity::STATUS_COMPLETED
        && $scheduledTaskSubscriber->getProcessed() === ScheduledTaskSubscriberEntity::STATUS_PROCESSED
      ) {
        return $scheduledTaskSubscriber;
      }
    }
    return null;
  }

  /**
   * @param array{newsletter: NewsletterEntity, queue: SendingQueueEntity, task: ScheduledTaskEntity} $source
   */
  private function createReplaySendingTask(array $source, SubscriberEntity $subscriber, int $segmentId, array $automationMeta): ScheduledTaskSubscriberEntity {
    $sourceTask = $source['task'];
    $sourceQueue = $source['queue'];
    $newsletter = $source['newsletter'];

    $meta = [
      NewsletterReplayMetadata::LATEST_NEWSLETTER_REPLAY => true,
      NewsletterReplayMetadata::REPLAY_SOURCE_NEWSLETTER_ID => $newsletter->getId(),
      NewsletterReplayMetadata::REPLAY_SOURCE_QUEUE_ID => $sourceQueue->getId(),
      NewsletterReplayMetadata::REPLAY_SOURCE_TASK_ID => $sourceTask->getId(),
      NewsletterReplayMetadata::REPLAY_SOURCE_PROCESSED_AT => $sourceTask->getProcessedAt()
        ? $sourceTask->getProcessedAt()->format('Y-m-d H:i:s')
        : null,
      NewsletterReplayMetadata::REPLAY_SEGMENT_ID => $segmentId,
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

    $taskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($taskSubscriber);

    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $task->setSendingQueue($queue);
    $queue->setMeta($meta);
    $queue->setNewsletter($newsletter);
    $queue->setCountToProcess(1);
    $queue->setCountTotal(1);
    $this->entityManager->persist($queue);

    return $taskSubscriber;
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
