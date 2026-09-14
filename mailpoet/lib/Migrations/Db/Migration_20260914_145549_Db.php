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
 */
class Migration_20260914_145549_Db extends DbMigration {
  public function run(): void {
    // The SQLite integration used by WordPress Playground does not support UPDATE with JOIN.
    if (Connection::isSQLite()) {
      return;
    }
    $this->markSendsAfterOptOut();
    $this->markQueuesWithoutTrackedLinks();
  }

  protected function getBatchSize(): int {
    return 1000;
  }

  private function markSendsAfterOptOut(): void {
    $statisticsTable = $this->getTableName(StatisticsNewsletterEntity::class);
    $subscribersTable = $this->getTableName(SubscriberEntity::class);
    $opensTable = $this->getTableName(StatisticsOpenEntity::class);
    $clicksTable = $this->getTableName(StatisticsClickEntity::class);
    $lastId = 0;
    do {
      $ids = $this->fetchIds(
        "SELECT id FROM `{$subscribersTable}`
         WHERE id > ? AND tracking_consent = ?
         ORDER BY id LIMIT " . $this->getBatchSize(),
        [$lastId, SubscriberEntity::TRACKING_CONSENT_DENIED]
      );
      if (!$ids) {
        return;
      }
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
      $lastId = end($ids);
    } while (count($ids) === $this->getBatchSize());
  }

  private function markQueuesWithoutTrackedLinks(): void {
    $statisticsTable = $this->getTableName(StatisticsNewsletterEntity::class);
    $queuesTable = $this->getTableName(SendingQueueEntity::class);
    $linksTable = $this->getTableName(NewsletterLinkEntity::class);
    $opensTable = $this->getTableName(StatisticsOpenEntity::class);
    $clicksTable = $this->getTableName(StatisticsClickEntity::class);
    $lastId = 0;
    do {
      $queues = $this->connection->fetchAllAssociative(
        "SELECT q.id, q.newsletter_id FROM `{$queuesTable}` q
         WHERE q.id > ?
           AND q.newsletter_id IS NOT NULL
           AND NOT EXISTS (SELECT 1 FROM `{$linksTable}` l WHERE l.queue_id = q.id)
           AND NOT EXISTS (SELECT 1 FROM `{$opensTable}` o WHERE o.queue_id = q.id)
           AND NOT EXISTS (SELECT 1 FROM `{$clicksTable}` c WHERE c.queue_id = q.id)
         ORDER BY q.id LIMIT " . $this->getBatchSize(),
        [$lastId]
      );
      if (!$queues) {
        return;
      }
      $queueIds = $this->toIntegers(array_column($queues, 'id'));
      // newsletter_id lets the update use the (newsletter_id, queue_id, subscriber_id) index.
      $this->connection->executeStatement(
        "UPDATE `{$statisticsTable}`
         SET sent_with_tracking = 0, sent_at = sent_at
         WHERE newsletter_id IN (:newsletterIds)
           AND queue_id IN (:queueIds)
           AND sent_with_tracking = 1",
        [
          'newsletterIds' => array_values(array_unique($this->toIntegers(array_column($queues, 'newsletter_id')))),
          'queueIds' => $queueIds,
        ],
        [
          'newsletterIds' => ArrayParameterType::INTEGER,
          'queueIds' => ArrayParameterType::INTEGER,
        ]
      );
      $lastId = end($queueIds);
    } while (count($queues) === $this->getBatchSize());
  }

  /**
   * @param mixed[] $params
   * @return int[]
   */
  private function fetchIds(string $sql, array $params): array {
    return $this->toIntegers($this->connection->fetchFirstColumn($sql, $params));
  }

  /**
   * @param mixed[] $values
   * @return int[]
   */
  private function toIntegers(array $values): array {
    return array_values(array_map('intval', array_filter($values, 'is_numeric')));
  }
}
