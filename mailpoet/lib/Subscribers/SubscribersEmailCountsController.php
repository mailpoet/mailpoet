<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SubscribersEmailCountsController {
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

    $queryParams = [
      'startId' => $startId,
      'endId' => $endId,
      'dayAgo' => $dayAgoIso,
    ];
    if ($dateLastProcessed) {
      $carbonDateLastProcessed = Carbon::createFromTimestamp($dateLastProcessed->getTimestamp());
      $dateFromIso = ($carbonDateLastProcessed->subDay())->toDateTimeString();
      $queryParams['dateFrom'] = $dateFromIso;
    }
    // If $dateLastProcessed provided, increment value, otherwise count all and reset value
    $initUpdateValue = $dateLastProcessed ? 's.email_count' : '';
    $dateLastProcessedSql = $dateLastProcessed ? ' AND st.processed_at >= :dateFrom' : '';

    $connection->executeQuery("
      UPDATE {$this->subscribersTable} as s
      JOIN (
          SELECT s.id, COUNT(st.id) as email_count
          FROM {$this->subscribersTable} as s
          JOIN {$scheduledTaskSubscribersTable} as sts ON s.id = sts.subscriber_id
          JOIN {$scheduledTasksTable} as st ON st.id = sts.task_id
          WHERE s.id >= :startId
          AND s.id <= :endId
          AND st.type = 'sending'
          AND st.processed_at IS NOT NULL
          AND st.processed_at < :dayAgo
          {$dateLastProcessedSql}
          GROUP BY s.id
      ) counts ON counts.id = s.id
      SET s.email_count = {$initUpdateValue} + IFNULL(counts.email_count, 0)
    ",
      $queryParams
    );

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
