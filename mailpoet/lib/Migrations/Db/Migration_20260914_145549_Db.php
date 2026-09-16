<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Doctrine\WPDB\Connection;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;

/**
 * Marks sends made before sent_with_tracking existed that went out without
 * tracking: recipients who had opted out before the send, and queues rendered
 * while site-wide tracking was off (those never store a tracked link, not even
 * the instant unsubscribe one).
 *
 * Recipients never asked on sites asking everyone cannot be recovered, because
 * nothing records when a site switched. Their rows keep today's numbers.
 *
 * A row is never marked when that recipient opened or clicked the email, so no
 * rate can end up counting an open from someone outside its denominator.
 *
 * Both passes walk their table in id windows and store how far they got, so one
 * statement stays short on a site with millions of subscribers or automation
 * queues, and a run cut short by a request timing out carries on from where it
 * stopped instead of starting again.
 */
class Migration_20260914_145549_Db extends DbMigration {
  private const PROGRESS_OPTION = 'mailpoet_sent_with_tracking_backfill';
  private const STEP_OPT_OUT = 'opt-out';
  private const STEP_LINKS = 'links';

  public function run(): void {
    // The SQLite integration used by WordPress Playground does not support UPDATE with JOIN.
    if (Connection::isSQLite()) {
      return;
    }
    $progress = $this->loadProgress();
    if ($progress['step'] === self::STEP_OPT_OUT) {
      $this->markSendsAfterOptOut($progress['lastId']);
      $progress = ['step' => self::STEP_LINKS, 'lastId' => 0];
      $this->saveProgress(self::STEP_LINKS, 0);
    }
    $this->markQueuesWithoutTrackedLinks($progress['lastId']);
    $this->clearProgress();
  }

  protected function getBatchSize(): int {
    return 1000;
  }

  /**
   * How many ids one statement may look at. Keeps a statement short on a table where almost
   * nothing matches, which is the normal case on a site that always had tracking on.
   */
  protected function getWindowSize(): int {
    return 10000;
  }

  private function markSendsAfterOptOut(int $lastId): void {
    $statisticsTable = $this->getTableName(StatisticsNewsletterEntity::class);
    $subscribersTable = $this->getTableName(SubscriberEntity::class);
    $opensTable = $this->getTableName(StatisticsOpenEntity::class);
    $clicksTable = $this->getTableName(StatisticsClickEntity::class);
    $maxId = $this->getMaxId($subscribersTable);
    while ($lastId < $maxId) {
      $windowEnd = min($lastId + $this->getWindowSize(), $maxId);
      $ids = $this->fetchIds(
        "SELECT id FROM `{$subscribersTable}`
         WHERE id > ? AND id <= ? AND tracking_consent = ?
         ORDER BY id LIMIT " . $this->getBatchSize(),
        [$lastId, $windowEnd, SubscriberEntity::TRACKING_CONSENT_DENIED]
      );
      if ($ids) {
        $this->connection->executeStatement(
          "UPDATE `{$statisticsTable}` sn
           INNER JOIN `{$subscribersTable}` s ON s.id = sn.subscriber_id
           SET sn.sent_with_tracking = 0, sn.sent_at = sn.sent_at
           WHERE sn.subscriber_id IN (:ids)
             AND sn.sent_with_tracking = 1
             AND s.tracking_consent_updated_at <= sn.sent_at
             AND NOT EXISTS (
               SELECT 1 FROM `{$opensTable}` o WHERE o.newsletter_id = sn.newsletter_id AND o.subscriber_id = sn.subscriber_id
             )
             AND NOT EXISTS (
               SELECT 1 FROM `{$clicksTable}` c WHERE c.newsletter_id = sn.newsletter_id AND c.subscriber_id = sn.subscriber_id
             )",
          ['ids' => $ids],
          ['ids' => ArrayParameterType::INTEGER]
        );
      }
      $lastId = count($ids) === $this->getBatchSize() ? (int)end($ids) : $windowEnd;
      $this->saveProgress(self::STEP_OPT_OUT, $lastId);
    }
  }

  private function markQueuesWithoutTrackedLinks(int $lastId): void {
    $statisticsTable = $this->getTableName(StatisticsNewsletterEntity::class);
    $queuesTable = $this->getTableName(SendingQueueEntity::class);
    $linksTable = $this->getTableName(NewsletterLinkEntity::class);
    $maxId = $this->getMaxId($queuesTable);
    while ($lastId < $maxId) {
      $windowEnd = min($lastId + $this->getWindowSize(), $maxId);
      $queues = $this->connection->fetchAllAssociative(
        "SELECT q.id, q.newsletter_id FROM `{$queuesTable}` q
         WHERE q.id > ? AND q.id <= ?
           AND q.newsletter_id IS NOT NULL
           AND NOT EXISTS (SELECT 1 FROM `{$linksTable}` l WHERE l.queue_id = q.id)
         ORDER BY q.id LIMIT " . $this->getBatchSize(),
        [$lastId, $windowEnd]
      );
      $queueIds = $this->toIntegers(array_column($queues, 'id'));
      $this->markQueues($statisticsTable, $queues, $this->withoutRecordedTracking($queueIds));
      $lastId = count($queues) === $this->getBatchSize() ? (int)end($queueIds) : $windowEnd;
      $this->saveProgress(self::STEP_LINKS, $lastId);
    }
  }

  /**
   * Opens and clicks are checked on the short list of candidates, not inside the scan above,
   * where they would cost two more index probes on every row of the queues table.
   *
   * @param int[] $queueIds
   * @return int[]
   */
  private function withoutRecordedTracking(array $queueIds): array {
    if (!$queueIds) {
      return [];
    }
    $recorded = [];
    foreach ([StatisticsOpenEntity::class, StatisticsClickEntity::class] as $entityClass) {
      $table = $this->getTableName($entityClass);
      $recorded = array_merge($recorded, $this->fetchIds(
        "SELECT DISTINCT queue_id FROM `{$table}` WHERE queue_id IN (:ids)",
        ['ids' => $queueIds],
        ['ids' => ArrayParameterType::INTEGER]
      ));
    }
    return array_values(array_diff($queueIds, $recorded));
  }

  /**
   * @param array<int, array<string, mixed>> $queues
   * @param int[] $queueIds
   */
  private function markQueues(string $statisticsTable, array $queues, array $queueIds): void {
    if (!$queueIds) {
      return;
    }
    $newsletterIds = [];
    foreach ($queues as $queue) {
      $queueId = $queue['id'] ?? null;
      $newsletterId = $queue['newsletter_id'] ?? null;
      if (!is_numeric($queueId) || !is_numeric($newsletterId)) {
        continue;
      }
      if (in_array((int)$queueId, $queueIds, true)) {
        $newsletterIds[] = (int)$newsletterId;
      }
    }
    // newsletter_id lets the update use the (newsletter_id, queue_id, subscriber_id) index.
    $this->connection->executeStatement(
      "UPDATE `{$statisticsTable}`
       SET sent_with_tracking = 0, sent_at = sent_at
       WHERE newsletter_id IN (:newsletterIds)
         AND queue_id IN (:queueIds)
         AND sent_with_tracking = 1",
      [
        'newsletterIds' => array_values(array_unique($newsletterIds)),
        'queueIds' => $queueIds,
      ],
      [
        'newsletterIds' => ArrayParameterType::INTEGER,
        'queueIds' => ArrayParameterType::INTEGER,
      ]
    );
  }

  private function getMaxId(string $table): int {
    $maxId = $this->connection->fetchOne("SELECT MAX(id) FROM `{$table}`");
    return is_numeric($maxId) ? (int)$maxId : 0;
  }

  /**
   * @return array{step: string, lastId: int}
   */
  private function loadProgress(): array {
    $stored = WPFunctions::get()->getOption(self::PROGRESS_OPTION);
    if (!is_array($stored)) {
      return ['step' => self::STEP_OPT_OUT, 'lastId' => 0];
    }
    $lastId = is_numeric($stored['lastId'] ?? null) ? (int)$stored['lastId'] : 0;
    $step = ($stored['step'] ?? null) === self::STEP_LINKS ? self::STEP_LINKS : self::STEP_OPT_OUT;
    return ['step' => $step, 'lastId' => $lastId];
  }

  private function saveProgress(string $step, int $lastId): void {
    WPFunctions::get()->updateOption(self::PROGRESS_OPTION, ['step' => $step, 'lastId' => $lastId], 'no');
  }

  private function clearProgress(): void {
    WPFunctions::get()->deleteOption(self::PROGRESS_OPTION);
  }

  /**
   * @param mixed[] $params
   * @param array<int|string, int> $types
   * @return int[]
   */
  private function fetchIds(string $sql, array $params, array $types = []): array {
    return $this->toIntegers($this->connection->fetchFirstColumn($sql, $params, $types));
  }

  /**
   * @param mixed[] $values
   * @return int[]
   */
  private function toIntegers(array $values): array {
    return array_values(array_map('intval', array_filter($values, 'is_numeric')));
  }
}
