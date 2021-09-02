<?php declare(strict_types=1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\ScheduledTask;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SubscribersLastEngagement extends SimpleWorker {
  const AUTOMATIC_SCHEDULING = false;
  const SUPPORT_MULTIPLE_INSTANCES = false;
  const BATCH_SIZE = 60;
  const TASK_TYPE = 'subscribers_last_engagement';

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    parent::__construct();
    $this->entityManager = $entityManager;
  }

  public function processTaskStrategy(ScheduledTask $task, $timer) {
    global $wpdb;
    $statisticsClicksTable = $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName();
    $statisticsOpensTable = $this->entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $postsTable = $wpdb->posts;
    $postsmetaTable = $wpdb->postmeta;
    $this->entityManager->getConnection()->executeStatement("
    UPDATE $subscribersTable as mps
      LEFT JOIN (SELECT max(created_at) as created_at, subscriber_id FROM $statisticsOpensTable as mpsoinner GROUP BY mpsoinner.subscriber_id) as mpso ON mpso.subscriber_id = mps.id
      LEFT JOIN (SELECT max(created_at) as created_at, subscriber_id FROM $statisticsClicksTable as mpscinner GROUP BY mpscinner.subscriber_id) as mpsc ON mpsc.subscriber_id = mps.id
      LEFT JOIN (SELECT MAX(post_id) AS post_id, meta_value as email FROM $postsmetaTable WHERE meta_key = '_billing_email' GROUP BY email) AS newestOrderIds ON newestOrderIds.email = mps.email
      LEFT JOIN (SELECT ID, post_date FROM $postsTable WHERE post_type = 'shop_order') AS shopOrders ON newestOrderIds.post_id = shopOrders.ID
    SET mps.last_engagement_at = NULLIF(GREATEST(COALESCE(mpso.created_at, 0), COALESCE(mpsc.created_at,0), COALESCE(shopOrders.post_date, 0)), 0)
    WHERE mps.last_engagement_at IS NULL;
    ");
  }
}
