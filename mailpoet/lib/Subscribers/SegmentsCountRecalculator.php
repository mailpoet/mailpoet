<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * Keeps SubscriberEntity::$segmentsCount in sync.
 *
 * segments_count is the number of the subscriber's subscribed memberships in
 * non-deleted segments. It mirrors the anti-join that used to power the
 * "Subscribers without a list" count, so the read can become
 * `WHERE segments_count = 0` instead of scanning every subscriber.
 *
 * Every recalculation re-derives the value from subscriber_segment + segments,
 * so it is idempotent: it is safe to call from several write paths, to call
 * twice, or to run concurrently with the backfill — the value always converges.
 * The semantics intentionally match the previous query exactly: only
 * status = 'subscribed' memberships in segments with deleted_at IS NULL are
 * counted, with no filtering by segment type (WP/WooCommerce segments count too).
 */
class SegmentsCountRecalculator {
  /** Subscribers touched per UPDATE when recalculating large/segment-wide sets. */
  private const BATCH_SIZE = 10000;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  /**
   * Recalculate the count for an explicit set of subscribers.
   *
   * @param int[] $subscriberIds
   */
  public function recalculateForSubscribers(array $subscriberIds): void {
    $subscriberIds = array_values(array_unique($subscriberIds));
    if ($subscriberIds === []) {
      return;
    }

    $subscribersTable = $this->getTableName(SubscriberEntity::class);
    $membershipSelect = $this->membershipCountSubquery('ssg.subscriber_id IN (:ids)');
    $connection = $this->entityManager->getConnection();

    foreach (array_chunk($subscriberIds, self::BATCH_SIZE) as $chunk) {
      $connection->executeStatement(
        "UPDATE {$subscribersTable} s
          LEFT JOIN ({$membershipSelect}) m ON m.subscriber_id = s.id
          SET s.segments_count = IFNULL(m.c, 0)
          WHERE s.id IN (:ids)",
        ['ids' => $chunk],
        ['ids' => ArrayParameterType::INTEGER]
      );
    }
  }

  /**
   * Recalculate the count for an inclusive range of subscriber ids.
   * Used by the backfill and reconcile workers.
   */
  public function recalculateForIdRange(int $minId, int $maxId): void {
    if ($minId > $maxId) {
      return;
    }

    $subscribersTable = $this->getTableName(SubscriberEntity::class);
    $membershipSelect = $this->membershipCountSubquery('ssg.subscriber_id BETWEEN :minId AND :maxId');

    $this->entityManager->getConnection()->executeStatement(
      "UPDATE {$subscribersTable} s
        LEFT JOIN ({$membershipSelect}) m ON m.subscriber_id = s.id
        SET s.segments_count = IFNULL(m.c, 0)
        WHERE s.id BETWEEN :minId AND :maxId",
      ['minId' => $minId, 'maxId' => $maxId]
    );
  }

  /**
   * Recalculate the count for every subscriber that has a membership in the
   * given segment. Used when a segment is trashed, restored or deleted, which
   * changes the count of all of its members at once.
   */
  public function recalculateForSegment(int $segmentId): void {
    $subscriberSegmentTable = $this->getTableName(SubscriberSegmentEntity::class);
    $connection = $this->entityManager->getConnection();

    $lastId = 0;
    do {
      $batchSize = self::BATCH_SIZE;
      $ids = $connection->executeQuery(
        "SELECT subscriber_id FROM {$subscriberSegmentTable}
          WHERE segment_id = :segmentId AND subscriber_id > :lastId
          ORDER BY subscriber_id ASC
          LIMIT {$batchSize}",
        ['segmentId' => $segmentId, 'lastId' => $lastId],
        ['segmentId' => ParameterType::INTEGER, 'lastId' => ParameterType::INTEGER]
      )->fetchFirstColumn();

      if ($ids === []) {
        break;
      }

      $subscriberIds = array_map(function ($id): int {
        return is_numeric($id) ? (int)$id : 0;
      }, $ids);
      $this->recalculateForSubscribers($subscriberIds);
      $lastId = (int)end($subscriberIds);
    } while (count($ids) === self::BATCH_SIZE);
  }

  private function membershipCountSubquery(string $subscriberCondition): string {
    $subscriberSegmentTable = $this->getTableName(SubscriberSegmentEntity::class);
    $segmentsTable = $this->getTableName(SegmentEntity::class);
    $subscribedStatus = SubscriberEntity::STATUS_SUBSCRIBED;

    return "SELECT ssg.subscriber_id, COUNT(*) AS c
      FROM {$subscriberSegmentTable} ssg
      JOIN {$segmentsTable} g ON g.id = ssg.segment_id AND g.deleted_at IS NULL
      WHERE ssg.status = '{$subscribedStatus}' AND {$subscriberCondition}
      GROUP BY ssg.subscriber_id";
  }

  /**
   * @param class-string $entityClass
   */
  private function getTableName(string $entityClass): string {
    return $this->entityManager->getClassMetadata($entityClass)->getTableName();
  }
}
