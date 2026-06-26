<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

/**
 * @extends Repository<ScheduledTaskQueuedSubscriberEntity>
 */
class ScheduledTaskQueuedSubscriberRepository extends Repository {
  protected function getEntityClassName() {
    return ScheduledTaskQueuedSubscriberEntity::class;
  }

  /** @param int[] $subscriberIds */
  public function addSubscribersByIds(ScheduledTaskEntity $task, array $subscriberIds): int {
    $subscriberIds = array_values(array_unique(array_filter(array_map('intval', $subscriberIds))));
    if ($subscriberIds === []) {
      return 0;
    }

    $scheduledTaskQueuedSubscribersTable = $this->entityManager->getClassMetadata(ScheduledTaskQueuedSubscriberEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    $result = $this->entityManager->getConnection()->executeQuery(
      "INSERT IGNORE INTO $scheduledTaskQueuedSubscribersTable
       (task_id, subscriber_id)
       SELECT ? as task_id, subscribers.`id` as subscriber_id
       FROM $subscribersTable subscribers
       WHERE subscribers.`deleted_at` IS NULL
       AND subscribers.`status` = ?
       AND subscribers.`id` IN (?)",
      [
        $task->getId(),
        SubscriberEntity::STATUS_SUBSCRIBED,
        $subscriberIds,
      ],
      [
        ParameterType::INTEGER,
        ParameterType::STRING,
        ArrayParameterType::INTEGER,
      ]
    );

    return (int)$result->rowCount();
  }

  public function getSubscriberIdsBatchForTask(int $taskId, int $lastProcessedSubscriberId, int $limit): array {
    $queryBuilder = $this->getBaseSubscribersIdsBatchForTaskQuery($taskId, $lastProcessedSubscriberId);
    /** @var string[] $subscribersIds */
    $subscribersIds = $queryBuilder
      ->select('IDENTITY(stsq.subscriber) AS subscriber_id')
      ->orderBy('stsq.subscriber', 'asc')
      ->setMaxResults($limit)
      ->getQuery()
      ->getSingleColumnResult();

    return array_map('intval', $subscribersIds);
  }

  public function countForTask(ScheduledTaskEntity $task): int {
    return $this->countBy(['task' => $task]);
  }

  public function countSubscriberIdsBatchForTask(int $taskId, int $lastProcessedSubscriberId): int {
    $queryBuilder = $this->getBaseSubscribersIdsBatchForTaskQuery($taskId, $lastProcessedSubscriberId);
    $count = $queryBuilder
      ->select('count(stsq.subscriber)')
      ->getQuery()
      ->getSingleScalarResult();

    return (int)$count;
  }

  public function checkCompleted(ScheduledTaskEntity $task): void {
    if ($task->getStatus() === ScheduledTaskEntity::STATUS_COMPLETED) {
      return;
    }
    if ($this->hasUnprocessed($task)) {
      return;
    }
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $task->setProcessedAt(Carbon::now()->millisecond(0));
    $this->entityManager->flush();
  }

  public function hasUnprocessed(ScheduledTaskEntity $task): bool {
    $scheduledTaskQueuedSubscribersTable = $this->entityManager->getClassMetadata(ScheduledTaskQueuedSubscriberEntity::class)->getTableName();
    $result = $this->entityManager->getConnection()->executeQuery(
      "SELECT 1 FROM $scheduledTaskQueuedSubscribersTable
       WHERE `task_id` = ?
       LIMIT 1",
      [$task->getId()],
      [ParameterType::INTEGER]
    )->fetchOne();

    return $result !== false;
  }

  public function deleteByScheduledTask(ScheduledTaskEntity $scheduledTask): void {
    $this->entityManager->createQueryBuilder()
      ->delete(ScheduledTaskQueuedSubscriberEntity::class, 'stsq')
      ->where('stsq.task = :task')
      ->setParameter('task', $scheduledTask)
      ->getQuery()
      ->execute();

    // delete was done via DQL, make sure the entities are also detached from the entity manager
    $this->detachAll(function (ScheduledTaskQueuedSubscriberEntity $entity) use ($scheduledTask) {
      return $entity->getTask() === $scheduledTask;
    });
  }

  public function deleteByScheduledTaskAndSubscriberIds(ScheduledTaskEntity $scheduledTask, array $subscriberIds): void {
    $this->entityManager->createQueryBuilder()
      ->delete(ScheduledTaskQueuedSubscriberEntity::class, 'stsq')
      ->where('stsq.task = :task')
      ->andWhere('stsq.subscriber IN (:subscriberIds)')
      ->setParameter('task', $scheduledTask)
      ->setParameter('subscriberIds', $subscriberIds, ArrayParameterType::INTEGER)
      ->getQuery()
      ->execute();

    // delete was done via DQL, make sure the entities are also detached from the entity manager
    $this->detachAll(function (ScheduledTaskQueuedSubscriberEntity $entity) use ($scheduledTask, $subscriberIds) {
      return $entity->getTask() === $scheduledTask && in_array($entity->getSubscriberId(), $subscriberIds, true);
    });
  }

  /** @param int[] $taskIds */
  public function deleteByTaskIds(array $taskIds): void {
    $this->entityManager->createQueryBuilder()
      ->delete(ScheduledTaskQueuedSubscriberEntity::class, 'stsq')
      ->where('stsq.task IN (:taskIds)')
      ->setParameter('taskIds', $taskIds)
      ->getQuery()
      ->execute();

    // delete was done via DQL, make sure the entities are also detached from the entity manager
    $this->detachAll(function (ScheduledTaskQueuedSubscriberEntity $entity) use ($taskIds) {
      $task = $entity->getTask();
      return $task && in_array($task->getId(), $taskIds, true);
    });
  }

  private function getBaseSubscribersIdsBatchForTaskQuery(int $taskId, int $lastProcessedSubscriberId): QueryBuilder {
    return $this->entityManager
      ->createQueryBuilder()
      ->from(ScheduledTaskQueuedSubscriberEntity::class, 'stsq')
      ->andWhere('stsq.task = :taskId')
      ->andWhere('stsq.subscriber > :lastProcessedSubscriberId')
      ->setParameter('taskId', $taskId)
      ->setParameter('lastProcessedSubscriberId', $lastProcessedSubscriberId);
  }
}
