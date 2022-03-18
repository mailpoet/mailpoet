<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersEmailCountsController;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SubscribersEmailCount extends SimpleWorker {
  const TASK_TYPE = 'subscribers_email_count';
  const BATCH_SIZE = 1000;
  const SUPPORT_MULTIPLE_INSTANCES = false;

  /** @var SubscribersEmailCountsController */
  private $subscribersEmailCountsController;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    SubscribersEmailCountsController $subscribersEmailCountsController,
    EntityManager $entityManager
  ) {
    $this->subscribersEmailCountsController = $subscribersEmailCountsController;
    $this->entityManager = $entityManager;
    parent::__construct();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $previousTask = $this->findPreviousTask($task);
    $dateFromLastRun = null;
    if ($previousTask instanceof ScheduledTaskEntity) {
      $dateFromLastRun = $previousTask->getScheduledAt();
    }

    $meta = $task->getMeta();
    $lastSubscriberId = $meta['last_subscriber_id'] ?? 0;
    $highestSubscriberId = $meta['highest_subscriber_id'] ?? $this->getHighestSubscriberId();
    $meta['highest_subscriber_id'] = $highestSubscriberId;
    $task->setMeta($meta);

    while ($lastSubscriberId <= $highestSubscriberId) {
      [$count, $lastSubscriberId] = $this->subscribersEmailCountsController->updateSubscribersEmailCounts($dateFromLastRun, self::BATCH_SIZE, $lastSubscriberId);
      if ($count === 0) {
        break;
      }

      $meta['last_subscriber_id'] = $lastSubscriberId++;
      $task->setMeta($meta);
      $this->scheduledTasksRepository->persist($task);
      $this->scheduledTasksRepository->flush();
      $this->cronHelper->enforceExecutionLimit($timer);
    };

    $this->schedule();
    return true;
  }

  private function findPreviousTask(ScheduledTaskEntity $task): ?ScheduledTaskEntity {
    return $this->scheduledTasksRepository->findPreviousTask($task);
  }

  private function getHighestSubscriberId(): int {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $result = $this->entityManager->getConnection()->executeQuery("SELECT MAX(id) FROM $subscribersTable LIMIT 1;")->fetchNumeric();
    /** @var int[] $result - it's required for PHPStan */
    return is_array($result) && isset($result[0]) ? (int)$result[0] : 0;
  }
}
