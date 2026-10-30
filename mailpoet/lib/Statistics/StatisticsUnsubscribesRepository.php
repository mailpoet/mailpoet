<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;

/**
 * @extends Repository<StatisticsUnsubscribeEntity>
 */
class StatisticsUnsubscribesRepository extends Repository {
  protected function getEntityClassName() {
    return StatisticsUnsubscribeEntity::class;
  }

  /**
   * Inserts one unsubscribe record per subscriber that is not yet unsubscribed.
   * Runs as a single INSERT ... SELECT so large selections do not go through
   * the ORM one entity at a time.
   *
   * @param int[] $subscriberIds
   * @return int Number of inserted rows
   */
  public function trackBulk(array $subscriberIds, string $source, string $method = StatisticsUnsubscribeEntity::METHOD_UNKNOWN): int {
    if ($subscriberIds === []) {
      return 0;
    }
    $statisticsTable = $this->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return (int)$this->entityManager->getConnection()->executeStatement(
      "INSERT INTO $statisticsTable (subscriber_id, source, method, created_at)
       SELECT id, :source, :method, :created_at
       FROM $subscribersTable
       WHERE id IN (:ids) AND status != :unsubscribed",
      [
        'ids' => array_map('intval', $subscriberIds),
        'source' => $source,
        'method' => $method,
        'created_at' => Carbon::now()->millisecond(0)->format('Y-m-d H:i:s'),
        'unsubscribed' => SubscriberEntity::STATUS_UNSUBSCRIBED,
      ],
      ['ids' => ArrayParameterType::INTEGER]
    );
  }

  public function getTotalForMonths(int $forMonths): int {
    $from = (new Carbon())->subMonths($forMonths);
    $count = $this->entityManager->createQueryBuilder()
      ->select('count(stats.id)')
      ->from(StatisticsUnsubscribeEntity::class, 'stats')
      ->andWhere('stats.createdAt >= :dateTime')
      ->setParameter('dateTime', $from)
      ->getQuery()
      ->getSingleScalarResult();

    return intval($count);
  }

  public function getCountPerMethodForMonths(int $forMonths): array {
    $from = (new Carbon())->subMonths($forMonths);
    return $this->entityManager->createQueryBuilder()
      ->select('count(stats.id) as count, stats.method as method')
      ->from(StatisticsUnsubscribeEntity::class, 'stats')
      ->andWhere('stats.createdAt >= :dateTime')
      ->groupBy('stats.method')
      ->setParameter('dateTime', $from)
      ->getQuery()
      ->getResult();
  }

  /**
   * @return array<int, array{month: string, method: string, count: int}>
   */
  public function getMonthlyCountsPerMethod(\DateTimeInterface $from): array {
    return $this->getMonthlyCounts(['method'], $from);
  }

  /**
   * All three breakdowns from one table scan. Unsubscribes without a submitted reason are
   * counted as REASON_UNSPECIFIED, rows without a recorded source as "unknown".
   *
   * @return array<int, array{month: string, method: string, source: string, reason: string, count: int}>
   */
  public function getMonthlyCountsPerMethodSourceAndReason(\DateTimeInterface $from): array {
    return $this->getMonthlyCounts(['method', 'source', 'reason'], $from);
  }

  /**
   * @param string[] $columns
   */
  private function getMonthlyCounts(array $columns, \DateTimeInterface $from): array {
    $statisticsTable = $this->getTableName();
    $values = [
      'method' => 'method',
      'reason' => 'COALESCE(reason, :unspecified)',
      'source' => 'COALESCE(source, :unknown)',
    ];
    // GROUP BY resolves table columns before aliases, so the aliases must not be column names.
    $select = implode(', ', array_map(function (string $column) use ($values): string {
      return "{$values[$column]} AS {$column}_value";
    }, $columns));
    $groupBy = implode(', ', array_map(function (string $column): string {
      return "{$column}_value";
    }, $columns));
    $rows = $this->entityManager->getConnection()->executeQuery(
      "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, $select, COUNT(id) AS count
       FROM $statisticsTable
       WHERE created_at >= :from
       GROUP BY month, $groupBy
       ORDER BY month, $groupBy",
      [
        'from' => $from->format('Y-m-d H:i:s'),
        'unspecified' => StatisticsUnsubscribeEntity::REASON_UNSPECIFIED,
        'unknown' => 'unknown',
      ]
    )->fetchAllAssociative();

    $counts = [];
    foreach ($rows as $row) {
      if (!is_string($row['month']) || !is_numeric($row['count'])) {
        continue;
      }
      $count = ['month' => $row['month']];
      foreach ($columns as $column) {
        if (!is_string($row["{$column}_value"])) {
          continue 2;
        }
        $count[$column] = $row["{$column}_value"];
      }
      $count['count'] = (int)$row['count'];
      $counts[] = $count;
    }
    return $counts;
  }

  public function findOneBySubscriberAndQueue(SubscriberEntity $subscriber, SendingQueueEntity $queue, NewsletterEntity $newsletter): ?StatisticsUnsubscribeEntity {
    $statistics = $this->findOneBy([
      'queue' => $queue,
      'newsletter' => $newsletter,
      'subscriber' => $subscriber,
    ]);
    return $statistics instanceof StatisticsUnsubscribeEntity ? $statistics : null;
  }

  public function findLatestForSubscriber(SubscriberEntity $subscriber): ?StatisticsUnsubscribeEntity {
    $result = $this->entityManager->createQueryBuilder()
      ->select('stats')
      ->from(StatisticsUnsubscribeEntity::class, 'stats')
      ->andWhere('stats.subscriber = :subscriber')
      ->setParameter('subscriber', $subscriber)
      ->orderBy('stats.createdAt', 'DESC')
      ->addOrderBy('stats.id', 'DESC')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();

    return $result instanceof StatisticsUnsubscribeEntity ? $result : null;
  }

  public function getReasonCountsForNewsletter(NewsletterEntity $newsletter): array {
    $reasonCounts = $this->entityManager->createQueryBuilder()
      ->select('stats.reason as reason, count(stats.id) as count')
      ->from(StatisticsUnsubscribeEntity::class, 'stats')
      ->andWhere('stats.newsletter = :newsletter')
      ->andWhere('stats.reason IS NOT NULL')
      ->groupBy('stats.reason')
      ->setParameter('newsletter', $newsletter)
      ->getQuery()
      ->getArrayResult();

    $unspecifiedCount = $this->entityManager->createQueryBuilder()
      ->select('count(stats.id)')
      ->from(StatisticsUnsubscribeEntity::class, 'stats')
      ->andWhere('stats.newsletter = :newsletter')
      ->andWhere('stats.reason IS NULL')
      ->setParameter('newsletter', $newsletter)
      ->getQuery()
      ->getSingleScalarResult();

    if ((int)$unspecifiedCount > 0) {
      $reasonCounts[] = [
        'reason' => StatisticsUnsubscribeEntity::REASON_UNSPECIFIED,
        'count' => $unspecifiedCount,
      ];
    }

    return $reasonCounts;
  }
}
