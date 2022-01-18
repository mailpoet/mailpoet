<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\Cron\Workers\Beamer;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\SendingQueue\Migration;
use MailPoet\Cron\Workers\SubscriberLinkTokens;
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

  public function create(string $type, ?string $status, \DateTimeInterface $scheduledAt, \DateTimeInterface $deletedAt = null) {
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus($status);
    $task->setScheduledAt($scheduledAt);

    if ($deletedAt) {
      $task->setDeletedAt($deletedAt);
    }

    $this->entityManager->persist($task);
    $this->entityManager->flush();

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
