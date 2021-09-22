<?php

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Models\ScheduledTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class ScheduledTasks {
  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    ScheduledTasksRepository $scheduledTasksRepository,
    WPFunctions $wp
  ) {
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->wp = $wp;
  }

  // Transition method needed while \MailPoet\Cron\Workers\Scheduler::verifySubscriber() and
  // \MailPoet\Cron\Workers\KeyCheck\KeyCheckWorker::processTaskStrategy() still use the old model ScheduledTask instead
  // of ScheduledEntity. This method can be removed once both methods don't use the old model anymore.
  public function oldRescheduleProgressively(ScheduledTask $task) {
    if ($task->id) {
      $taskEntity = $this->scheduledTasksRepository->findOneById($task->id);
      if ($taskEntity instanceof ScheduledTaskEntity) {
        $this->rescheduleProgressively($taskEntity);
      }
    }
  }

  public function rescheduleProgressively(ScheduledTaskEntity $task): int {
    $scheduledAt = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    $rescheduleCount = $task->getRescheduleCount();
    $timeout = (int)min(ScheduledTaskEntity::BASIC_RESCHEDULE_TIMEOUT * pow(2, $rescheduleCount), ScheduledTaskEntity::MAX_RESCHEDULE_TIMEOUT);
    $task->setScheduledAt($scheduledAt->addMinutes($timeout));
    $task->setRescheduleCount($rescheduleCount + 1);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    return $timeout;
  }
}
