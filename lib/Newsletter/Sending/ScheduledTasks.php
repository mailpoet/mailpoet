<?php

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class ScheduledTasks {
  /** @var ScheduledTasksRepository */
  public $repository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    ScheduledTasksRepository $repository,
    WPFunctions $wp
  ) {
    $this->repository = $repository;
    $this->wp = $wp;
  }

  public function rescheduleProgressively(ScheduledTaskEntity $task): int {
    $scheduledAt = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    $rescheduleCount = $task->getRescheduleCount();
    $timeout = (int)min(ScheduledTaskEntity::BASIC_RESCHEDULE_TIMEOUT * pow(2, $rescheduleCount), ScheduledTaskEntity::MAX_RESCHEDULE_TIMEOUT);
    $task->setScheduledAt($scheduledAt->addMinutes($timeout));
    $task->setRescheduleCount($rescheduleCount + 1);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->repository->persist($task);
    $this->repository->flush();

    return $timeout;
  }
}
