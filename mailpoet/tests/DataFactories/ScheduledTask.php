<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\Cron\Workers\Beamer;
use MailPoet\Cron\Workers\Bounce;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck;
use MailPoet\Cron\Workers\SendingQueue\Migration;
use MailPoet\Cron\Workers\SubscriberLinkTokens;
use MailPoet\Cron\Workers\SubscribersStatsReport;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Cron\Workers\WooCommercePastOrders;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class ScheduledTask {
  /**
   * @var EntityManager
   */
  private $entityManager;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function __construct() {
    $diContainer = ContainerWrapper::getInstance();
    $this->entityManager = $diContainer->get(EntityManager::class);
    $this->scheduledTasksRepository = $diContainer->get(ScheduledTasksRepository::class);
  }

  public function create(
    string $type,
    ?string $status,
    ?\DateTimeInterface $scheduledAt = null,
    \DateTimeInterface $deletedAt = null,
    \DateTimeInterface $updatedAt = null
  ) {
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus($status);
    if ($scheduledAt) {
      $task->setScheduledAt($scheduledAt);
    }

    if ($deletedAt) {
      $task->setDeletedAt($deletedAt);
    }

    $this->entityManager->persist($task);
    $this->entityManager->flush();

    // workaround for storing updatedAt because it's set in TimestampListener
    if ($updatedAt) {
      $tasksTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
      $this->entityManager->getConnection()->executeQuery("
        UPDATE $tasksTable
        SET updated_at = '{$updatedAt->format('Y-m-d H:i:s')}'
        WHERE id = {$task->getId()}
      ");
      $this->entityManager->refresh($task);
    }

    return $task;
  }

  /**
   * Reschedules tasks created after plugin activation so that they don't block cron tasks in tests
   */
  public function withDefaultTasks() {
    $datetime = Carbon::createFromTimestamp((int)WPFunctions::get()->currentTime('timestamp'));
    $datetime->addDay();
    $this->scheduleTask(WooCommercePastOrders::TASK_TYPE, $datetime);
    $this->scheduleTask(UnsubscribeTokens::TASK_TYPE, $datetime);
    $this->scheduleTask(SubscriberLinkTokens::TASK_TYPE, $datetime);
    $this->scheduleTask(Beamer::TASK_TYPE, $datetime);
    $this->scheduleTask(InactiveSubscribers::TASK_TYPE, $datetime);
    $this->scheduleTask(Migration::TASK_TYPE, $datetime);
    $this->scheduleTask(PremiumKeyCheck::TASK_TYPE, $datetime);
    $this->scheduleTask(SendingServiceKeyCheck::TASK_TYPE, $datetime);
    $this->scheduleTask(Bounce::TASK_TYPE, $datetime);
    $this->scheduleTask(SubscribersStatsReport::TASK_TYPE, $datetime);
  }

  private function scheduleTask(string $type, Carbon $datetime) {
    $task = $this->scheduledTasksRepository->findOneBy([
      'type' => $type,
    ]);

    if (!($task instanceof ScheduledTaskEntity)) {
      $task = new ScheduledTaskEntity();
    }

    $task->setType($type);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setScheduledAt($datetime);

    $this->entityManager->persist($task);
    $this->entityManager->flush();
  }
}
