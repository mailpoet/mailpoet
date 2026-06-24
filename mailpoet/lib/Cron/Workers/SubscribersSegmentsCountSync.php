<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Doctrine\WPDB\Connection;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SegmentsCountRecalculator;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * Populates and reconciles SubscriberEntity::$segmentsCount.
 *
 * The first run is the backfill: it sweeps the whole subscribers table by id
 * range, recomputes segments_count for every row, and then flips the
 * SEGMENTS_COUNT_BACKFILLED setting so reads start trusting the column.
 *
 * Every subsequent (weekly) run is the reconcile backstop: it re-sweeps the
 * table to repair any drift left by a write path that forgot to update the
 * column. Both phases use the same idempotent recompute, so the value always
 * converges. Work is chunked and bounded by enforceExecutionLimit() so the
 * sweep never runs as one long query and never blocks a request.
 */
class SubscribersSegmentsCountSync extends SimpleWorker {
  const TASK_TYPE = 'subscribers_segments_count_sync';
  const BATCH_SIZE = 5000;
  const SUPPORT_MULTIPLE_INSTANCES = false;
  const BACKFILLED_SETTING_KEY = 'subscribers_segments_count_backfilled';

  /** @var EntityManager */
  private $entityManager;

  /** @var SegmentsCountRecalculator */
  private $segmentsCountRecalculator;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    EntityManager $entityManager,
    SegmentsCountRecalculator $segmentsCountRecalculator,
    SettingsController $settings
  ) {
    parent::__construct();
    $this->entityManager = $entityManager;
    $this->segmentsCountRecalculator = $segmentsCountRecalculator;
    $this->settings = $settings;
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer): bool {
    // The recalculator relies on UPDATE ... LEFT JOIN, which the SQLite
    // integration in WordPress Playground does not support. Make the task a
    // no-op there and never flip the backfill flag, so reads stay on the
    // anti-join fallback instead of trusting an unpopulated column.
    if (Connection::isSQLite()) {
      return true;
    }

    $meta = $task->getMeta();
    $lastId = isset($meta['last_subscriber_id']) ? (int)$meta['last_subscriber_id'] : 0;
    $highestId = $this->getHighestSubscriberId();

    while ($lastId < $highestId) {
      $this->segmentsCountRecalculator->recalculateForIdRange($lastId + 1, $lastId + self::BATCH_SIZE);
      $lastId += self::BATCH_SIZE;
      $task->setMeta(['last_subscriber_id' => $lastId]);
      $this->scheduledTasksRepository->persist($task);
      $this->scheduledTasksRepository->flush();
      $this->cronHelper->enforceExecutionLimit($timer); // throws and reschedules when over the limit
    }

    // Reset progress so the next (reconcile) run starts a fresh sweep.
    // Flush this before flipping the backfill flag: if the process dies
    // between the two writes, the next run resumes from 0 rather than from
    // a stale mid-table cursor while reads already trust the column.
    $task->setMeta(['last_subscriber_id' => 0]);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    // The whole table has been recomputed: reads can trust segments_count now.
    $this->settings->set(self::BACKFILLED_SETTING_KEY, true);

    return true;
  }

  private function getHighestSubscriberId(): int {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $result = $this->entityManager->getConnection()->executeQuery("SELECT MAX(id) FROM $subscribersTable LIMIT 1;")->fetchNumeric();
    return is_array($result) && isset($result[0]) && is_numeric($result[0]) ? (int)$result[0] : 0;
  }
}
