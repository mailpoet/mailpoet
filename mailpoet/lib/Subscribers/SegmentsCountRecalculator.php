<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Workers\SubscribersSegmentsCountSync;
use MailPoet\DI\ContainerWrapper;
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
  public const BATCH_SIZE = 10000;

  /**
   * When a single segment change would recompute at least this many memberships
   * inline, defer to the background sweep instead. Read via static:: so tests
   * can lower it without inserting hundreds of thousands of rows.
   */
  protected const DEFER_THRESHOLD = 200000;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  public function getDeferThreshold(): int {
    return static::DEFER_THRESHOLD;
  }

  /**
   * Hand a recalculation off to the background sweep (scheduled to run as soon
   * as possible) instead of doing it inline. Used when a single change touches
   * too many subscribers to recompute within one request. The sweep re-derives
   * every subscriber's count from source, so it converges regardless of what
   * the deferred change was.
   *
   * CronWorkerScheduler is resolved lazily rather than injected: it transitively
   * depends on SegmentsRepository, which depends on this class, so a constructor
   * dependency would form a circular reference (the same reason SimpleWorker
   * pulls it from the container).
   */
  public function scheduleBackgroundRecalculation(): void {
    // The sweep worker no-ops on SQLite and reads never trust the column there,
    // so there is nothing to schedule.
    if (Connection::isSQLite()) {
      return;
    }
    ContainerWrapper::getInstance()
      ->get(CronWorkerScheduler::class)
      ->scheduleImmediatelyIfNotRunning(SubscribersSegmentsCountSync::TASK_TYPE);
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

    // Recomputing a multi-million-member segment inline would blow the request
    // budget, so hand the largest changes to the background sweep instead.
    if ($this->countSegmentMembers($segmentIds, $subscribedOnly) >= $this->getDeferThreshold()) {
      $this->scheduleBackgroundRecalculation();
      return;
    }

    $subscriberSegmentTable = $this->getTableName(SubscriberSegmentEntity::class);
    $connection = $this->entityManager->getConnection();

    $lastId = 0;
    do {
      $batchSize = self::BATCH_SIZE;
      $sql = "SELECT DISTINCT subscriber_id FROM {$subscriberSegmentTable}
          WHERE segment_id IN (:segmentIds) AND subscriber_id > :lastId";
      $params = ['segmentIds' => $segmentIds, 'lastId' => $lastId];
      $types = ['segmentIds' => ArrayParameterType::INTEGER, 'lastId' => ParameterType::INTEGER];
      if ($subscribedOnly) {
        $sql .= ' AND status = :status';
        $params['status'] = SubscriberEntity::STATUS_SUBSCRIBED;
        $types['status'] = ParameterType::STRING;
      }
      $sql .= ' ORDER BY subscriber_id ASC LIMIT ' . $batchSize;
      $ids = $connection->executeQuery($sql, $params, $types)->fetchFirstColumn();

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

  /**
   * Count the memberships a segment change would touch. Uses COUNT(*) rather
   * than COUNT(DISTINCT subscriber_id): a subscriber shared across several of
   * the given segments is counted more than once, but an over-count only makes
   * the deferral threshold trip slightly earlier, which is safe.
   *
   * When $type is null the query stays on the segment_id index with no join,
   * which is what the recalculation path wants. Pass a $type to scope the count
   * to segments of that type (joining the segments table), matching a
   * type-scoped delete.
   *
   * @param int[] $segmentIds
   */
  public function countSegmentMembers(array $segmentIds, bool $subscribedOnly, ?string $type = null): int {
    if ($segmentIds === []) {
      return 0;
    }

    $subscriberSegmentTable = $this->getTableName(SubscriberSegmentEntity::class);
    $sql = "SELECT COUNT(*) FROM {$subscriberSegmentTable} ss";
    $params = ['segmentIds' => $segmentIds];
    $types = ['segmentIds' => ArrayParameterType::INTEGER];
    if ($type !== null) {
      $segmentsTable = $this->getTableName(SegmentEntity::class);
      $sql .= " JOIN {$segmentsTable} s ON ss.segment_id = s.id AND s.type = :type";
      $params['type'] = $type;
      $types['type'] = ParameterType::STRING;
    }
    $sql .= " WHERE ss.segment_id IN (:segmentIds)";
    if ($subscribedOnly) {
      $sql .= ' AND ss.status = :status';
      $params['status'] = SubscriberEntity::STATUS_SUBSCRIBED;
      $types['status'] = ParameterType::STRING;
    }
    $count = $this->entityManager->getConnection()->executeQuery($sql, $params, $types)->fetchOne();
    return is_numeric($count) ? (int)$count : 0;
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
