<?php declare(strict_types = 1);

namespace MailPoet\Util\DataInconsistency;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\SendingQueue;

class DataInconsistencyRepositoryTest extends \MailPoetTest {
  private DataInconsistencyRepository $repository;

  public function _before() {
    $this->repository = $this->diContainer->get(DataInconsistencyRepository::class);
  }

  public function testItFetchesOrphanedSendingTasksCount() {
    $orphanedSendingTasksCount = $this->repository->getOrphanedSendingTasksCount();
    verify($orphanedSendingTasksCount)->equals(0);

    // Add non orphaned sending task
    $okTask = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    (new SendingQueue())->create($okTask);
    $orphanedSendingTasksCount = $this->repository->getOrphanedSendingTasksCount();
    verify($orphanedSendingTasksCount)->equals(0);

    // Add orphaned sending tasks
    (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, null);
    $orphanedSendingTasksCount = $this->repository->getOrphanedSendingTasksCount();
    verify($orphanedSendingTasksCount)->equals(2);
  }
}
