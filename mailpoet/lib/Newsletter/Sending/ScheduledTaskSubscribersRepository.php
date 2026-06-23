<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * @extends Repository<ScheduledTaskSubscriberEntity>
 */
class ScheduledTaskSubscribersRepository extends Repository {
  /** @var ScheduledTaskQueuedSubscriberRepository */
  private $scheduledTaskQueuedSubscriberRepository;

  public function __construct(
    EntityManager $entityManager,
    ScheduledTaskQueuedSubscriberRepository $scheduledTaskQueuedSubscriberRepository
  ) {
    parent::__construct($entityManager);
    $this->scheduledTaskQueuedSubscriberRepository = $scheduledTaskQueuedSubscriberRepository;
  }

  protected function getEntityClassName() {
    return ScheduledTaskSubscriberEntity::class;
  }

  public function isSubscriberProcessed(ScheduledTaskEntity $task, SubscriberEntity $subscriber): bool {
    $scheduledTaskSubscriber = $this
      ->doctrineRepository
      ->createQueryBuilder('sts')
      ->andWhere('sts.processed = 1')
      ->andWhere('sts.task = :task')
      ->andWhere('sts.subscriber = :subscriber')
      ->setParameter('subscriber', $subscriber)
      ->setParameter('task', $task)
      ->getQuery()
      ->getOneOrNullResult();
    return !empty($scheduledTaskSubscriber);
  }

  /** @param int[] $ids */
  public function deleteByTaskIds(array $ids): void {
    $this->entityManager->createQueryBuilder()
      ->delete(ScheduledTaskSubscriberEntity::class, 'sts')
      ->where('sts.task IN (:taskIds)')
      ->setParameter('taskIds', $ids)
      ->getQuery()
      ->execute();

    // delete was done via DQL, make sure the entities are also detached from the entity manager
    $this->detachAll(function (ScheduledTaskSubscriberEntity $entity) use ($ids) {
      $task = $entity->getTask();
      return $task && in_array($task->getId(), $ids, true);
    });

    // also clear any pending (queued) rows for these sending tasks; no-op for non-sending tasks
    $this->scheduledTaskQueuedSubscriberRepository->deleteByTaskIds($ids);
  }

  public function deleteByScheduledTask(ScheduledTaskEntity $scheduledTask): void {
    $this->entityManager->createQueryBuilder()
      ->delete(ScheduledTaskSubscriberEntity::class, 'sts')
      ->where('sts.task = :task')
      ->setParameter('task', $scheduledTask)
      ->getQuery()
      ->execute();

    // delete was done via DQL, make sure the entities are also detached from the entity manager
    $this->detachAll(function (ScheduledTaskSubscriberEntity $entity) use ($scheduledTask) {
      return $entity->getTask() === $scheduledTask;
    });

    // also clear any pending (queued) rows for this sending task; no-op for non-sending tasks
    $this->scheduledTaskQueuedSubscriberRepository->deleteByScheduledTask($scheduledTask);
  }

  public function saveError(ScheduledTaskEntity $scheduledTask, int $subscriberId, string $errorMessage): void {
    $scheduledTaskSubscriber = $this->findOneBy(['task' => $scheduledTask, 'subscriber' => $subscriberId]);

    if ($scheduledTaskSubscriber instanceof ScheduledTaskSubscriberEntity) {
      $scheduledTaskSubscriber->setFailed(ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED);
      $scheduledTaskSubscriber->setProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED);
      $scheduledTaskSubscriber->setError($errorMessage);
      $this->persist($scheduledTaskSubscriber);
      $this->flush();

      $this->checkCompleted($scheduledTask);
    }
  }

  public function countProcessed(ScheduledTaskEntity $scheduledTaskEntity): int {
    return $this->countBy(['task' => $scheduledTaskEntity, 'processed' => ScheduledTaskSubscriberEntity::STATUS_PROCESSED]);
  }

  private function countUnprocessed(ScheduledTaskEntity $scheduledTaskEntity): int {
    return $this->countBy(['task' => $scheduledTaskEntity, 'processed' => ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED]);
  }

  public function purgeOldTaskSubscribers(int $daysToKeep, int $taskBatchSize, int $rowLimit): int {
    $stTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $stsTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $cutoff = Carbon::now()->subDays($daysToKeep)->toDateTimeString();

    $taskIds = $this->entityManager->getConnection()->executeQuery(
      "SELECT DISTINCT st.`id`
       FROM `{$stTable}` st
       INNER JOIN `{$stsTable}` sts ON sts.`task_id` = st.`id`
       WHERE st.`type` = :type
         AND st.`status` = :status
         AND st.`processed_at` < :cutoff
         AND st.`deleted_at` IS NULL
       LIMIT :taskBatchSize",
      [
        'type' => 'sending',
        'status' => ScheduledTaskEntity::STATUS_COMPLETED,
        'cutoff' => $cutoff,
        'taskBatchSize' => $taskBatchSize,
      ],
      [
        'type' => ParameterType::STRING,
        'status' => ParameterType::STRING,
        'cutoff' => ParameterType::STRING,
        'taskBatchSize' => ParameterType::INTEGER,
      ]
    )->fetchFirstColumn();

    if (!$taskIds) {
      return 0;
    }

    /** @var int[] $taskIds */
    $taskIdsList = implode(',', array_map('intval', $taskIds));

    $deleted = $this->entityManager->getConnection()->executeStatement(
      "DELETE FROM `{$stsTable}`
       WHERE `task_id` IN ({$taskIdsList})
       LIMIT :rowLimit",
      [
        'rowLimit' => $rowLimit,
      ],
      [
        'rowLimit' => ParameterType::INTEGER,
      ]
    );

    return (int)$deleted;
  }

  public function purgeCompletedBounceTaskSubscribers(int $taskBatchSize, int $rowLimit): int {
    $stTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $stsTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();

    $taskIds = $this->entityManager->getConnection()->executeQuery(
      "SELECT DISTINCT st.`id`
       FROM `{$stTable}` st
       INNER JOIN `{$stsTable}` sts ON sts.`task_id` = st.`id`
       WHERE st.`type` = :type
         AND st.`status` = :status
         AND st.`deleted_at` IS NULL
       LIMIT :taskBatchSize",
      [
        'type' => 'bounce',
        'status' => ScheduledTaskEntity::STATUS_COMPLETED,
        'taskBatchSize' => $taskBatchSize,
      ],
      [
        'type' => ParameterType::STRING,
        'status' => ParameterType::STRING,
        'taskBatchSize' => ParameterType::INTEGER,
      ]
    )->fetchFirstColumn();

    if (!$taskIds) {
      return 0;
    }

    /** @var int[] $taskIds */
    $taskIdsList = implode(',', array_map('intval', $taskIds));

    $deleted = $this->entityManager->getConnection()->executeStatement(
      "DELETE FROM `{$stsTable}`
       WHERE `task_id` IN ({$taskIdsList})
       LIMIT :rowLimit",
      [
        'rowLimit' => $rowLimit,
      ],
      [
        'rowLimit' => ParameterType::INTEGER,
      ]
    );

    return (int)$deleted;
  }

  private function checkCompleted(ScheduledTaskEntity $task): void {
    $count = $this->countUnprocessed($task);
    if ($count === 0) {
      $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
      $task->setProcessedAt(Carbon::now()->millisecond(0));
      $this->entityManager->flush();
    }
  }
}
