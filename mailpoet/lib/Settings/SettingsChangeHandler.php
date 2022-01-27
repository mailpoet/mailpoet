<?php

namespace MailPoet\Settings;

use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SettingsChangeHandler {

  /**
   * @var ScheduledTasksRepository
   */
  private $scheduledTasksRepository;

  public function __construct(
    ScheduledTasksRepository $scheduledTasksRepository
  ) {
    $this->scheduledTasksRepository = $scheduledTasksRepository;
  }

  public function onSubscribeOldWoocommerceCustomersChange(): void {
    $task = $this->scheduledTasksRepository->findOneBy([
      'type' => WooCommerceSync::TASK_TYPE,
      'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
    ]);
    if (!($task instanceof ScheduledTaskEntity)) {
      $task = $this->createScheduledTask(WooCommerceSync::TASK_TYPE);
    }
    $datetime = Carbon::createFromTimestamp((int)WPFunctions::get()->currentTime('timestamp'));
    $task->setScheduledAt($datetime->subMinute());
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }

  public function onInactiveSubscribersIntervalChange(): void {
    $task = $this->scheduledTasksRepository->findOneBy([
      'type' => InactiveSubscribers::TASK_TYPE,
      'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
    ]);
    if (!($task instanceof ScheduledTaskEntity)) {
      $task = $this->createScheduledTask(InactiveSubscribers::TASK_TYPE);
    }
    $datetime = Carbon::createFromTimestamp((int)WPFunctions::get()->currentTime('timestamp'));
    $task->setScheduledAt($datetime->subMinute());
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }

  private function createScheduledTask(string $type): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    return $task;
  }
}
