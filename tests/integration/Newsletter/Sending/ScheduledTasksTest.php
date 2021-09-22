<?php

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Models\ScheduledTask;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoetVendor\Carbon\Carbon;

class ScheduledTasksTest extends \MailPoetTest {
  /** @var ScheduledTasks */
  private $scheduledTasks;

  /**
   * @var ScheduledTaskFactory
   */
  private $scheduledTaskFactory;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->scheduledTasks = $this->diContainer->get(ScheduledTasks::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
  }

  public function testItCanRescheduleTasksProgressively() {
    $task = $this->scheduledTaskFactory->create('test', null, new Carbon());
    $scheduledAt = $task->getScheduledAt();

    $timeout = $this->scheduledTasks->rescheduleProgressively($task);
    expect($timeout)->equals(ScheduledTaskEntity::BASIC_RESCHEDULE_TIMEOUT);
    expect($scheduledAt < $task->getScheduledAt())->true();
    expect($task->getStatus())->equals(ScheduledTask::STATUS_SCHEDULED);

    $timeout = $this->scheduledTasks->rescheduleProgressively($task);
    expect($timeout)->equals(ScheduledTaskEntity::BASIC_RESCHEDULE_TIMEOUT * 2);

    $task->setRescheduleCount(123456); // too many
    $timeout = $this->scheduledTasks->rescheduleProgressively($task);
    expect($timeout)->equals(ScheduledTaskEntity::MAX_RESCHEDULE_TIMEOUT);
  }

  public function cleanup() {
    $this->truncateEntity(ScheduledTaskEntity::class);
  }
}
