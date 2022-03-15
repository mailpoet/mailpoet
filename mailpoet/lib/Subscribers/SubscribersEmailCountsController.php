<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SubscribersEmailCountsController {
  private $processedTaskIdsTableCreated = false;

  /** @var EntityManager */
  private $entityManager;

  /** @var string */
  private $subscribersTable;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
    $this->subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
  }

  public function updateSubscribersEmailCounts(?\DateTimeInterface $dateLastProcessed, int $batchSize, ?int $startId = null): array {
    $sendingQueuesTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $scheduledTasksTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $scheduledTaskSubscribersTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();

    $connection = $this->entityManager->getConnection();

    $dayAgo = new Carbon();
    $dayAgoIso = $dayAgo->subDay()->toDateTimeString();

    $startId = (int)$startId;

    [$countSubscribersToUpdate, $endId] = $this->countAndMaxOfSubscribersInRange($startId, $batchSize);
    if (!$countSubscribersToUpdate) {
      return [0, 0];
    }

    // Temporary table with processed tasks from threshold date up to yesterday
    $processedTaskIdsTable = 'processed_task_ids';
    if (!$this->processedTaskIdsTableCreated) {
      $queryParams = [];
      $processedTaskIdsTableSql = "
        CREATE TEMPORARY TABLE IF NOT EXISTS {$processedTaskIdsTable}
        (INDEX task_id_ids (id))
        SELECT DISTINCT task_id as id FROM {$sendingQueuesTable} as sq
          JOIN {$scheduledTasksTable} as st ON sq.task_id = st.id
          WHERE st.processed_at IS NOT NULL
          AND st.processed_at < :dayAgo";
      $queryParams['dayAgo'] = $dayAgoIso;

      if ($dateLastProcessed) {
        $processedTaskIdsTableSql .= " AND st.processed_at >= :dateFrom";
        $carbonDateLastProcessed = Carbon::createFromTimestamp($dateLastProcessed->getTimestamp());
        $dateFromIso = ($carbonDateLastProcessed->subDay())->toDateTimeString();
        $queryParams['dateFrom'] = $dateFromIso;
      }

      $resultQuery = $connection->executeQuery($processedTaskIdsTableSql, $queryParams);
      $this->processedTaskIdsTableCreated = true;

      if ($resultQuery->rowCount() === 0) return [0,0];
    }

    // Temporary table needed for UPDATE query
    // mySQL does not allow to modify the same table used in the select
    $subscriberIdsEmailsCountTmpTable = 'subscribers_ids_email_counts';
    $connection->executeQuery("
      CREATE TEMPORARY TABLE IF NOT EXISTS {$subscriberIdsEmailsCountTmpTable}
      (UNIQUE subscriber_id (id))
        SELECT s.id, count(task_ids.id) as emails_count from {$this->subscribersTable} s
        JOIN {$scheduledTaskSubscribersTable} sts ON s.id = sts.subscriber_id
        JOIN {$processedTaskIdsTable} task_ids ON task_ids.id = sts.task_id
        WHERE s.id >= :startId
        AND s.id <= :endId
        GROUP BY s.id
    ",
      [
        'startId' => $startId,
        'endId' => $endId,
      ]
    );

    // If $dateLastProcessed provided, increment value, otherwise count all and reset value
    $initUpdateValue = $dateLastProcessed ? 's.emails_count' : '';
    $updateQuery = $connection->executeQuery("
      UPDATE {$this->subscribersTable} as s
      JOIN {$subscriberIdsEmailsCountTmpTable} as sc ON s.id = sc.id
      SET s.emails_count = {$initUpdateValue} + IFNULL(sc.emails_count, 0)
      WHERE s.id >= :startId
      AND s.id <= :endId
    ",
      [
        'startId' => $startId,
        'endId' => $endId,
      ]
    );
    $connection->executeQuery("DROP TABLE {$subscriberIdsEmailsCountTmpTable}");

    return [$countSubscribersToUpdate, $endId];
  }

  private function countAndMaxOfSubscribersInRange(int $startId, int $batchSize): array {
    $connection = $this->entityManager->getConnection();

    $result = $connection->executeQuery("
      SELECT s.id FROM {$this->subscribersTable} as s
      WHERE s.id >= :startId
      ORDER BY s.id
      LIMIT :batchSize
    ",
      [
        'startId' => $startId,
        'batchSize' => $batchSize,
      ],
      [
        'startId' => \PDO::PARAM_INT,
        'batchSize' => \PDO::PARAM_INT,
      ]
    );

    $subscribersInRange = $result->fetchAllAssociative();

    $countSubscribersInRange = count(array_map(
      function ($id) {
        return (int)$id['id'];
      },
      $subscribersInRange
    ));

    if (!$countSubscribersInRange) {
      return [0,0];
    }
    return [$countSubscribersInRange,$subscribersInRange[$countSubscribersInRange - 1]['id']];
  }
}
