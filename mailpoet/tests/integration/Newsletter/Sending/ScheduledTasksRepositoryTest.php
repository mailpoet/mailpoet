<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Cron\Workers\SendingQueue\Migration;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue;
use MailPoetVendor\Carbon\Carbon;

class ScheduledTasksRepositoryTest extends \MailPoetTest {
  /** @var ScheduledTasksRepository */
  private $repository;

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  /** @var SendingQueue */
  private $sendingQueueFactory;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->repository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->sendingQueueFactory = new SendingQueue();
  }

  public function testItCanGetDueTasks() {
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay(), Carbon::now()); // deleted (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay()); // scheduled in future (should not be fetched)
    $this->scheduledTaskFactory->create('test', '', Carbon::now()->subDay()); // wrong status (should not be fetched)
    $expectedResult[] = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay()); // due (scheduled in past)
    $expectedResult[] = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay()); // due (scheduled in past)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay()); // due (scheduled in past)

    $tasks = $this->repository->findDueByType('test', 2);
    $this->assertCount(2, $tasks);
    $this->assertSame($expectedResult, $tasks);
  }

  public function testItCanGetRunningTasks() {
    $expectedResult[] = $this->scheduledTaskFactory->create('test', null, Carbon::now()->subDay()); // running (scheduled in past)
    $this->scheduledTaskFactory->create('test', null, Carbon::now()->subDay(), Carbon::now()); // deleted (should not be fetched)
    $this->scheduledTaskFactory->create('test', null, Carbon::now()->addDay()); // scheduled in future (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay()); // wrong status (should not be fetched)

    $tasks = $this->repository->findRunningByType('test', 10);
    $this->assertSame($expectedResult, $tasks);
  }

  public function testItCanGetCompletedTasks() {
    $expectedResult[] = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay()); // completed (scheduled in past)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay(), Carbon::now()); // deleted (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->addDay()); // scheduled in future (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay()); // wrong status (should not be fetched)

    $tasks = $this->repository->findCompletedByType('test', 10);
    $this->assertSame($expectedResult, $tasks);
  }

  public function testItCanGetFutureScheduledTasks() {
    $expectedResult[] = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay()); // scheduled (in future)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay(), Carbon::now()); // deleted (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay()); // scheduled in past (should not be fetched)
    $this->scheduledTaskFactory->create('test', null, Carbon::now()->addDay()); // wrong status (should not be fetched)

    $tasks = $this->repository->findFutureScheduledByType('test', 10);
    $this->assertSame($expectedResult, $tasks);
  }

  public function testItCanGetRunningSendingTasks(): void {
    // running task
    $task = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, null, Carbon::now()->subDay());
    $this->sendingQueueFactory->create($task);
    $expectedResult[] = $task;
    // deleted task
    $task = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, null, Carbon::now()->subDay(), Carbon::now());
    $this->sendingQueueFactory->create($task);
    // without sending queue
    $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, null, Carbon::now()->subDay());
    // scheduled in future
    $task = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->addDay());
    $this->sendingQueueFactory->create($task);
    // wrong status
    $task = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay());
    $this->sendingQueueFactory->create($task);

    $tasks = $this->repository->findRunningSendingTasks();
    $this->assertSame($expectedResult, $tasks);
  }

  public function testCanCountByStatus(){
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay());
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDays(20));
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_PAUSED, Carbon::now()->addDay());
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_PAUSED, Carbon::now()->addDays(3));
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_PAUSED, Carbon::now()->addDays(5));
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_INVALID, Carbon::now()->addDays(4));
    $this->scheduledTaskFactory->create('sending', NULL, Carbon::now()->addDays(4));

    $counts = $this->repository->getCountsPerStatus();
    $this->assertEquals([
      ScheduledTaskEntity::STATUS_SCHEDULED => 2,
      ScheduledTaskEntity::STATUS_PAUSED => 3,
      ScheduledTaskEntity::STATUS_INVALID => 1,
      ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING => 1,
      ScheduledTaskEntity::STATUS_COMPLETED => 0,
    ], $counts);
  }

  public function testItCanFetchBasicTasksData() {
    $this->scheduledTaskFactory->create(SendingTask::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay());
    $this->scheduledTaskFactory->create(Migration::TASK_TYPE, ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING, Carbon::now()->addDay());
    $data = $this->repository->getLatestTasks();
    expect(count($data))->equals(2);
    $ids = array_map(function ($d){ return $d->getId(); }, $data);
    $types = array_map(function ($d){ return $d->getType(); }, $data);
    $this->assertContains(1, $ids);
    $this->assertContains(2, $ids);
    $this->assertContains(SendingTask::TASK_TYPE, $types);
    $this->assertContains(Migration::TASK_TYPE, $types);
    expect(is_int($data[1]->getPriority()))->true();
    expect($data[1]->getUpdatedAt())->isInstanceOf(\DateTimeInterface::class);
    expect($data[1]->getStatus())->notEmpty();
    expect($data[0])->isInstanceOf(ScheduledTaskEntity::class);
    expect($data[1])->isInstanceOf(ScheduledTaskEntity::class);
  }

  public function testItCanFilterTasksByType() {
    $this->scheduledTaskFactory->create(SendingTask::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->addDay());
    $this->scheduledTaskFactory->create(Migration::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->addDay());
    $data = $this->repository->getLatestTasks(Migration::TASK_TYPE);
    expect(count($data))->equals(1);
    expect($data[0]->getType())->equals(Migration::TASK_TYPE);
  }

  public function testItCanFilterTasksByStatus() {
    $this->scheduledTaskFactory->create(SendingTask::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->addDay());
    $this->scheduledTaskFactory->create(SendingTask::TASK_TYPE, ScheduledTaskEntity::STATUS_PAUSED, Carbon::now()->addDay());
    $data = $this->repository->getLatestTasks(null, [ScheduledTaskEntity::STATUS_COMPLETED]);
    expect(count($data))->equals(1);
    expect($data[0]->getStatus())->equals(ScheduledTaskEntity::STATUS_COMPLETED);
  }

  public function testItDoesNotFailForSendingTaskWithoutQueue() {
    $this->scheduledTaskFactory->create(SendingTask::TASK_TYPE, 'any', Carbon::now()->addDay());
    $data = $this->repository->getLatestTasks();
    expect(count($data))->equals(1);
  }

  public function cleanup() {
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
  }
}
