<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Migrator\AppMigration;
use MailPoetVendor\Doctrine\DBAL\Connection;

class Migration_20250501_114655_App extends AppMigration {
  public function run(): void {
    $clicksStatsTable = $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName();
    $unsubscribeStatsTable = $this->entityManager->getClassMetadata(StatisticsUnsubscribeEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subscribersSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();

    // First get all subscriber IDs that were unsubscribed by a bot
    $subscriberIds = $this->entityManager->getConnection()->executeQuery(
      "SELECT DISTINCT mp_unsub.subscriber_id
       FROM {$unsubscribeStatsTable} AS mp_unsub
       LEFT JOIN {$clicksStatsTable} AS mp_click
         ON mp_unsub.newsletter_id = mp_click.newsletter_id
         AND mp_unsub.subscriber_id = mp_click.subscriber_id
         AND ABS(TIMESTAMPDIFF(SECOND, mp_click.created_at, mp_unsub.created_at)) <= 4
       WHERE mp_unsub.created_at > '2025-03-01'
       GROUP BY mp_unsub.subscriber_id
       HAVING COUNT(mp_click.id) >= 3"
    )->fetchFirstColumn();

    if (empty($subscriberIds)) {
      return;
    }

    // Then switch the global subscriber status to subscribed
    $this->entityManager->getConnection()->executeQuery(
      "UPDATE {$subscribersTable}
       SET status = :subscribedStatus
       WHERE id IN (:subscriberIds)
       AND status = :unsubscribedStatus",
      [
        'subscribedStatus' => SubscriberEntity::STATUS_SUBSCRIBED,
        'unsubscribedStatus' => SubscriberEntity::STATUS_UNSUBSCRIBED,
        'subscriberIds' => $subscriberIds,
      ],
      [
        'subscriberIds' => Connection::PARAM_INT_ARRAY,
      ]
    );

    // Update the subscriber_segment table, find rows that were unsubscribed at the same time
    $this->entityManager->getConnection()->executeQuery(
      "UPDATE {$subscribersSegmentsTable} AS mp_subseg
       JOIN {$unsubscribeStatsTable} AS mp_unsub
         ON mp_subseg.subscriber_id = mp_unsub.subscriber_id
         AND ABS(TIMESTAMPDIFF(SECOND, mp_subseg.updated_at, mp_unsub.created_at)) <= 2
       SET mp_subseg.status = :subscribedStatus
       WHERE mp_subseg.status = :unsubscribedStatus
       AND mp_subseg.subscriber_id IN (:subscriberIds)",
      [
        'subscribedStatus' => SubscriberEntity::STATUS_SUBSCRIBED,
        'unsubscribedStatus' => SubscriberEntity::STATUS_UNSUBSCRIBED,
        'subscriberIds' => $subscriberIds,
      ],
      [
        'subscriberIds' => Connection::PARAM_INT_ARRAY,
      ]
    );
  }
}
