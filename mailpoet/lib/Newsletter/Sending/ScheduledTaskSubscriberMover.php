<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class ScheduledTaskSubscriberMover {
  const BACKFILL_BATCH_SIZE = 10000;

  private EntityManager $entityManager;
  private ScheduledTaskQueuedSubscriberRepository $scheduledTaskQueuedSubscriberRepository;
  private ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository;

  public function __construct(
    EntityManager $entityManager,
    ScheduledTaskQueuedSubscriberRepository $scheduledTaskQueuedSubscriberRepository,
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository
  ) {
    $this->entityManager = $entityManager;
    $this->scheduledTaskQueuedSubscriberRepository = $scheduledTaskQueuedSubscriberRepository;
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
  }

  /**
   * @param int[] $subscriberIds
   */
  public function moveProcessedToLog(ScheduledTaskEntity $task, array $subscriberIds): void {
    $subscriberIds = $this->normalizeSubscriberIds($subscriberIds);
    if ($subscriberIds === []) {
      return;
    }

    $this->entityManager->getConnection()->transactional(function (Connection $connection) use ($task, $subscriberIds): void {
      $this->insertProcessedToLog($connection, $task, $subscriberIds);
      $this->deleteFromQueue($connection, $task, $subscriberIds);
    });
    $this->detachQueuedFromIdentityMap($task, $subscriberIds);
  }

  public function moveFailedToLog(ScheduledTaskEntity $task, int $subscriberId, string $error): void {
    $this->entityManager->getConnection()->transactional(function (Connection $connection) use ($task, $subscriberId, $error): void {
      $this->insertFailedToLog($connection, $task, $subscriberId, $error);
      $this->deleteFromQueue($connection, $task, [$subscriberId]);
    });
    $this->detachQueuedFromIdentityMap($task, [$subscriberId]);
  }

  public function moveBackToQueue(ScheduledTaskEntity $task, int $subscriberId): void {
    $this->entityManager->getConnection()->transactional(function (Connection $connection) use ($task, $subscriberId): void {
      $deleted = $this->deleteFromLog($connection, $task, $subscriberId);
      if ($deleted === 0) {
        return;
      }

      $this->insertToQueue($connection, $task, $subscriberId);
    });
    $this->detachLogFromIdentityMap($task, $subscriberId);
  }

  /**
   * Moves the pending (processed = 0) log rows of the given tasks into the queue,
   * in batches. Used by the queue/log split migration and by the data-integrity
   * recovery when a migration was interrupted.
   *
   * Each batch INSERT-then-DELETEs in a single transaction, entirely in the
   * database — the queue itself records what was moved, so the DELETE removes the
   * pending log rows that now have a queue counterpart. INSERT before DELETE so an
   * interrupted batch can only duplicate a row (harmless while sending is paused),
   * never drop a pending recipient. Every committed batch permanently shrinks the
   * remaining work, so a retried run only does what's left, with no cursor to
   * track. Idempotent: INSERT IGNORE skips rows already in the queue.
   *
   * Safe against over-deleting: a post-split pending recipient lives in the log
   * XOR the queue, and post-upgrade enqueues write only the queue (no processed=0
   * log row), so the join only ever matches rows this backfill just moved.
   *
   * Pending rows are expected to live only in the queue afterwards, so callers
   * holding ScheduledTaskSubscriberEntity instances for the moved rows should
   * re-query rather than trust the identity map.
   *
   * @param int[] $taskIds
   * @return int Number of pending log rows moved to the queue.
   */
  public function backfillPendingToQueue(array $taskIds, int $batchSize = self::BACKFILL_BATCH_SIZE): int {
    $taskIds = array_values(array_unique(array_filter($taskIds)));
    if ($taskIds === []) {
      return 0;
    }

    $logTable = $this->getLogTableName();
    $queueTable = $this->getQueueTableName();
    $connection = $this->entityManager->getConnection();
    $movedCount = 0;

    do {
      $movedInBatch = (int)$connection->transactional(function (Connection $connection) use ($taskIds, $batchSize, $logTable, $queueTable): int {
        $connection->executeStatement(
          "INSERT IGNORE INTO $queueTable (`task_id`, `subscriber_id`, `created_at`)
           SELECT log.`task_id`, log.`subscriber_id`, COALESCE(log.`created_at`, log.`updated_at`, NOW())
           FROM $logTable log
           WHERE log.`task_id` IN (:taskIds) AND log.`processed` = :unprocessed
           LIMIT :limit",
          [
            'taskIds' => $taskIds,
            'unprocessed' => ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
            'limit' => $batchSize,
          ],
          [
            'taskIds' => ArrayParameterType::INTEGER,
            'unprocessed' => ParameterType::INTEGER,
            'limit' => ParameterType::INTEGER,
          ]
        );

        return (int)$connection->executeStatement(
          "DELETE log FROM $logTable log
           JOIN $queueTable queue
             ON queue.`task_id` = log.`task_id` AND queue.`subscriber_id` = log.`subscriber_id`
           WHERE log.`task_id` IN (:taskIds) AND log.`processed` = :unprocessed",
          [
            'taskIds' => $taskIds,
            'unprocessed' => ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
          ],
          [
            'taskIds' => ArrayParameterType::INTEGER,
            'unprocessed' => ParameterType::INTEGER,
          ]
        );
      });
      $movedCount += $movedInBatch;
    } while ($movedInBatch > 0);

    return $movedCount;
  }

  /**
   * Moves are executed with raw SQL, so Doctrine's identity map still holds the
   * rows we just relocated. Detach them so a later lookup re-reads from the
   * database instead of returning a stale, already-moved entity. Callers should
   * re-query the repositories rather than trust $task's in-memory subscriber
   * collections after a move.
   *
   * @param int[] $subscriberIds
   */
  private function detachQueuedFromIdentityMap(ScheduledTaskEntity $task, array $subscriberIds): void {
    $this->scheduledTaskQueuedSubscriberRepository->detachAll(function (ScheduledTaskQueuedSubscriberEntity $entity) use ($task, $subscriberIds) {
      return $entity->getTask() === $task && in_array($entity->getSubscriberId(), $subscriberIds, true);
    });
  }

  private function detachLogFromIdentityMap(ScheduledTaskEntity $task, int $subscriberId): void {
    $this->scheduledTaskSubscribersRepository->detachAll(function (ScheduledTaskSubscriberEntity $entity) use ($task, $subscriberId) {
      return $entity->getTask() === $task && $entity->getSubscriberId() === $subscriberId;
    });
  }

  /**
   * @param int[] $subscriberIds
   * @return int[]
   */
  private function normalizeSubscriberIds(array $subscriberIds): array {
    return array_values(array_unique(array_filter(array_map('intval', $subscriberIds))));
  }

  /**
   * @param int[] $subscriberIds
   */
  private function insertProcessedToLog(Connection $connection, ScheduledTaskEntity $task, array $subscriberIds): void {
    $scheduledTaskSubscribersTable = $this->getLogTableName();
    $scheduledTaskQueuedSubscribersTable = $this->getQueueTableName();

    $connection->executeStatement(
      "INSERT INTO $scheduledTaskSubscribersTable
       (`task_id`, `subscriber_id`, `processed`, `failed`, `error`, `created_at`, `updated_at`)
       SELECT stsq.`task_id`, stsq.`subscriber_id`, :processed, :failed, NULL, stsq.`created_at`, NOW()
       FROM $scheduledTaskQueuedSubscribersTable stsq
       WHERE stsq.`task_id` = :taskId
       AND stsq.`subscriber_id` IN (:subscriberIds)",
      [
        'processed' => ScheduledTaskSubscriberEntity::STATUS_PROCESSED,
        'failed' => ScheduledTaskSubscriberEntity::FAIL_STATUS_OK,
        'taskId' => $task->getId(),
        'subscriberIds' => $subscriberIds,
      ],
      [
        'processed' => ParameterType::INTEGER,
        'failed' => ParameterType::INTEGER,
        'taskId' => ParameterType::INTEGER,
        'subscriberIds' => ArrayParameterType::INTEGER,
      ]
    );
  }

  private function insertFailedToLog(Connection $connection, ScheduledTaskEntity $task, int $subscriberId, string $error): void {
    $scheduledTaskSubscribersTable = $this->getLogTableName();
    $scheduledTaskQueuedSubscribersTable = $this->getQueueTableName();

    $connection->executeStatement(
      "INSERT INTO $scheduledTaskSubscribersTable
       (`task_id`, `subscriber_id`, `processed`, `failed`, `error`, `created_at`, `updated_at`)
       SELECT stsq.`task_id`, stsq.`subscriber_id`, :processed, :failed, :error, stsq.`created_at`, NOW()
       FROM $scheduledTaskQueuedSubscribersTable stsq
       WHERE stsq.`task_id` = :taskId
       AND stsq.`subscriber_id` = :subscriberId",
      [
        'processed' => ScheduledTaskSubscriberEntity::STATUS_PROCESSED,
        'failed' => ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED,
        'error' => $error,
        'taskId' => $task->getId(),
        'subscriberId' => $subscriberId,
      ],
      [
        'processed' => ParameterType::INTEGER,
        'failed' => ParameterType::INTEGER,
        'error' => ParameterType::STRING,
        'taskId' => ParameterType::INTEGER,
        'subscriberId' => ParameterType::INTEGER,
      ]
    );
  }

  /**
   * @param int[] $subscriberIds
   */
  private function deleteFromQueue(Connection $connection, ScheduledTaskEntity $task, array $subscriberIds): void {
    $scheduledTaskQueuedSubscribersTable = $this->getQueueTableName();

    $connection->executeStatement(
      "DELETE FROM $scheduledTaskQueuedSubscribersTable
       WHERE `task_id` = :taskId
       AND `subscriber_id` IN (:subscriberIds)",
      [
        'taskId' => $task->getId(),
        'subscriberIds' => $subscriberIds,
      ],
      [
        'taskId' => ParameterType::INTEGER,
        'subscriberIds' => ArrayParameterType::INTEGER,
      ]
    );
  }

  private function deleteFromLog(Connection $connection, ScheduledTaskEntity $task, int $subscriberId): int {
    $scheduledTaskSubscribersTable = $this->getLogTableName();

    return (int)$connection->executeStatement(
      "DELETE FROM $scheduledTaskSubscribersTable
       WHERE `task_id` = :taskId
       AND `subscriber_id` = :subscriberId",
      [
        'taskId' => $task->getId(),
        'subscriberId' => $subscriberId,
      ],
      [
        'taskId' => ParameterType::INTEGER,
        'subscriberId' => ParameterType::INTEGER,
      ]
    );
  }

  private function insertToQueue(Connection $connection, ScheduledTaskEntity $task, int $subscriberId): void {
    $scheduledTaskQueuedSubscribersTable = $this->getQueueTableName();

    $connection->executeStatement(
      "INSERT INTO $scheduledTaskQueuedSubscribersTable
       (`task_id`, `subscriber_id`, `created_at`)
       VALUES (:taskId, :subscriberId, NOW())",
      [
        'taskId' => $task->getId(),
        'subscriberId' => $subscriberId,
      ],
      [
        'taskId' => ParameterType::INTEGER,
        'subscriberId' => ParameterType::INTEGER,
      ]
    );
  }

  private function getQueueTableName(): string {
    return $this->entityManager->getClassMetadata(ScheduledTaskQueuedSubscriberEntity::class)->getTableName();
  }

  private function getLogTableName(): string {
    return $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
  }
}
