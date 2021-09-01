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
    $statisticsClicksTable = $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName();
    $statisticsOpensTable = $this->entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement("
    UPDATE $subscribersTable as mps
      LEFT JOIN (SELECT max(created_at) as created_at, subscriber_id FROM $statisticsOpensTable as mpsoinner GROUP BY mpsoinner.subscriber_id) as mpso ON mpso.subscriber_id = mps.id
      LEFT JOIN (SELECT max(created_at) as created_at, subscriber_id FROM $statisticsClicksTable as mpscinner GROUP BY mpscinner.subscriber_id) as mpsc ON mpsc.subscriber_id = mps.id
    SET mps.last_engagement_at = GREATEST(COALESCE(mpso.created_at, 0), COALESCE(mpsc.created_at, 0))
    WHERE mps.last_engagement_at IS NULL;
    ");
  }
}
