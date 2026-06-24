<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Doctrine\WPDB\Connection;
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
    // The UPDATE ... LEFT JOIN syntax below is not supported by the SQLite
    // integration used in WordPress Playground. Reads stay on the anti-join
    // there because the sync worker never flips the backfill flag (see
    // SubscribersSegmentsCountSync::processTaskStrategy()).
    if (Connection::isSQLite()) {
      return;
    }

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
    // See recalculateForSubscribers(): UPDATE ... LEFT JOIN is unsupported on SQLite.
    if (Connection::isSQLite()) {
      return;
    }

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
  public function recalculateForSegment(int $segmentId, bool $subscribedOnly = true): void {
    $this->recalculateForSegments([$segmentId], $subscribedOnly);
  }

  /**
   * Recalculate the count for every subscriber that has a membership in any of
   * the given segments. Used when segments are trashed, restored or deleted,
   * which changes the count of all of their members at once.
   *
   * Members are walked in keyset-paginated batches rather than materialized into
   * one array, so this stays memory-safe even on multi-million-member segments.
   *
   * $subscribedOnly = true (default): only walk members whose current
   * subscriber_segment.status = 'subscribed'. Safe when the segment's
   * deleted_at changed but no membership statuses changed — non-subscribed
   * members were never counted and their recomputation is a no-op.
   *
   * $subscribedOnly = false: walk all members regardless of status. Required
   * when the caller performed raw-SQL writes that may have changed membership
   * statuses (e.g. the WooCommerce sync), so subscribers transitioning away
   * from subscribed must also be recomputed.
   *
   * @param int[] $segmentIds
   */
  public function recalculateForSegments(array $segmentIds, bool $subscribedOnly = true): void {
    // recalculateForSubscribers() is a no-op on SQLite, so skip the walk too.
    if (Connection::isSQLite()) {
      return;
    }

    $segmentIds = array_values(array_unique(array_map('intval', $segmentIds)));
    if ($segmentIds === []) {
      return;
    }

    $subscriberSegmentTable = $this->getTableName(SubscriberSegmentEntity::class);
    $connection = $this->entityManager->getConnection();

    $statusFilter = $subscribedOnly
      ? "AND status = '" . SubscriberEntity::STATUS_SUBSCRIBED . "'"
      : '';
    $lastId = 0;
    do {
      $batchSize = self::BATCH_SIZE;
      $ids = $connection->executeQuery(
        "SELECT DISTINCT subscriber_id FROM {$subscriberSegmentTable}
          WHERE segment_id IN (:segmentIds) {$statusFilter} AND subscriber_id > :lastId
          ORDER BY subscriber_id ASC
          LIMIT {$batchSize}",
        ['segmentIds' => $segmentIds, 'lastId' => $lastId],
        ['segmentIds' => ArrayParameterType::INTEGER, 'lastId' => ParameterType::INTEGER]
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
