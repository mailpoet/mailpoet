<?php

namespace MailPoet\Cron;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Models\ScheduledTask;
use MailPoet\Newsletter\Sending\ScheduledTasks;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class CronWorkerRunner {
  const TASK_BATCH_SIZE = 5;
  const TASK_RUN_TIMEOUT = 120;
  const TIMED_OUT_TASK_RESCHEDULE_TIMEOUT = 5;

  /** @var float */
  private $timer;

  /** @var CronHelper */
  private $cronHelper;

  /** @var CronWorkerScheduler */
  private $cronWorkerScheduler;

  /** @var WPFunctions */
  private $wp;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** * @var ScheduledTasks */
  private $scheduledTasks;

  public function __construct(
      CronHelper $cronHelper,
      CronWorkerScheduler $cronWorkerScheduler,
      WPFunctions $wp,
      ScheduledTasksRepository $scheduledTasksRepository,
      ScheduledTasks $scheduledTasks
  ) {
    $this->timer = microtime(true);
    $this->cronHelper = $cronHelper;
    $this->cronWorkerScheduler = $cronWorkerScheduler;
    $this->wp = $wp;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->scheduledTasks = $scheduledTasks;
  }

  public function run(CronWorkerInterface $worker) {
    // abort if execution limit is reached
    $this->cronHelper->enforceExecutionLimit($this->timer);
    $dueTasks = $this->getDueTasks($worker);
    $runningTasks = $this->getRunningTasks($worker);

    if (!$worker->checkProcessingRequirements()) {
      foreach (array_merge($dueTasks, $runningTasks) as $task) {
        $this->scheduledTasksRepository->remove($task);
        $this->scheduledTasksRepository->flush();
      }
      return false;
    }

    $worker->init();

    if (!$dueTasks && !$runningTasks) {
      if ($worker->scheduleAutomatically()) {
        $this->cronWorkerScheduler->schedule($worker->getTaskType(), $worker->getNextRunDate());
      }
      return false;
    }

    $task = null;
    try {
      foreach ($dueTasks as $i => $task) {
        $this->prepareTask($worker, $task);
      }
      foreach ($runningTasks as $i => $task) {
        $this->processTask($worker, $task);
      }
    } catch (\Exception $e) {
      if ($task && $e->getCode() !== CronHelper::DAEMON_EXECUTION_LIMIT_REACHED) {
        $this->scheduledTasks->rescheduleProgressively($task);
      }
      throw $e;
    }

    return true;
  }

  private function getDueTasks(CronWorkerInterface $worker) {
    return $this->scheduledTasksRepository->findDueByType($worker->getTaskType(), self::TASK_BATCH_SIZE);
  }

  private function getRunningTasks(CronWorkerInterface $worker) {
    return $this->scheduledTasksRepository->findRunningByType($worker->getTaskType(), self::TASK_BATCH_SIZE);
  }

  private function prepareTask(CronWorkerInterface $worker, ScheduledTaskEntity $task) {
    // abort if execution limit is reached
    $this->cronHelper->enforceExecutionLimit($this->timer);

    $prepareCompleted = $worker->prepareTaskStrategy($this->convertTaskClass($task), $this->timer);
    if ($prepareCompleted) {
      $task->setStatus(null);
      $this->scheduledTasksRepository->persist($task);
      $this->scheduledTasksRepository->flush();
    }
  }

  private function processTask(CronWorkerInterface $worker, ScheduledTaskEntity $task) {
    // abort if execution limit is reached
    $this->cronHelper->enforceExecutionLimit($this->timer);

    if (!$worker->supportsMultipleInstances()) {
      if ($this->rescheduleOutdated($task)) {
        return false;
      }
      if ($this->isInProgress($task)) {
        return false;
      }
    }

    $this->startProgress($task);

    try {
      $completed = $worker->processTaskStrategy($this->convertTaskClass($task), $this->timer);
    } catch (\Exception $e) {
      $this->stopProgress($task);
      throw $e;
    }

    if ($completed) {
      $this->complete($task);
    }

    $this->stopProgress($task);

    return (bool)$completed;
  }

  private function rescheduleOutdated(ScheduledTaskEntity $task) {
    $currentTime = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));

    if (empty($task->getUpdatedAt())) {
      // missing updatedAt, consider this task outdated (set year to 2000) and reschedule
      $updatedAt = Carbon::createFromDate(2000);
    } else if (!$task->getUpdatedAt() instanceof Carbon) {
      $updatedAt = new Carbon($task->getUpdatedAt());
    } else {
      $updatedAt = $task->getUpdatedAt();
    }

    // If the task is running for too long consider it stuck and reschedule
    if (!empty($task->getUpdatedAt()) && $updatedAt->diffInMinutes($currentTime, false) > self::TASK_RUN_TIMEOUT) {
      $this->stopProgress($task);
      $this->cronWorkerScheduler->reschedule($task, self::TIMED_OUT_TASK_RESCHEDULE_TIMEOUT);
      return true;
    }
    return false;
  }

  private function isInProgress(ScheduledTaskEntity $task) {
    if ($task->getInProgress()) {
      // Do not run multiple instances of the task
      return true;
    }
    return false;
  }

  private function startProgress(ScheduledTaskEntity $task) {
    $task->setInProgress(true);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }

  private function stopProgress(ScheduledTaskEntity $task) {
    $task->setInProgress(false);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }

  private function complete(ScheduledTaskEntity $task) {
    $task->setProcessedAt(new Carbon());
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }

  // temporary function to convert an ScheduledTaskEntity object to ScheduledTask while we don't migrate the Workers
  // to use Doctrine entities
  private function convertTaskClass(ScheduledTaskEntity $doctrineTask): ScheduledTask {
    $parisTask = ScheduledTask::findOne($doctrineTask->getId());

    if (!$parisTask instanceof ScheduledTask) {
      throw new \Exception('Unable to find scheduled task.');
    }

    return $parisTask;
  }
}
