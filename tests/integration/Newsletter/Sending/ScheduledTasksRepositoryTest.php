<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoetVendor\Carbon\Carbon;

class ScheduledTasksRepositoryTest extends \MailPoetTest {
  /** @var ScheduledTasksRepository */
  private $repository;

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->repository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
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

  public function cleanup() {
    $this->truncateEntity(ScheduledTaskEntity::class);
  }
}
