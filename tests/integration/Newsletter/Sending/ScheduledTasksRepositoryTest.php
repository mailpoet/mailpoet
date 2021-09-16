<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Models\ScheduledTask;
use MailPoetVendor\Carbon\Carbon;

class ScheduledTasksRepositoryTest extends \MailPoetTest {
  /** @var ScheduledTasksRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->repository = $this->diContainer->get(ScheduledTasksRepository::class);
  }

  public function testItCanGetDueTasks() {
    $this->createScheduledTask('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay(), Carbon::now()); // deleted (should not be fetched)
    $this->createScheduledTask('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay()); // scheduled in future (should not be fetched)
    $this->createScheduledTask('test', '', Carbon::now()->subDay()); // wrong status (should not be fetched)
    $expectedResult[] = $this->createScheduledTask('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay()); // due (scheduled in past)
    $expectedResult[] = $this->createScheduledTask('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay()); // due (scheduled in past)
    $this->createScheduledTask('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay()); // due (scheduled in past)

    $tasks = $this->repository->findDueByType('test', 2);
    $this->assertCount(2, $tasks);
    $this->assertSame($expectedResult, $tasks);
  }

  public function testItCanGetRunningTasks() {
    $expectedResult[] = $this->createScheduledTask('test', null, Carbon::now()->subDay()); // running (scheduled in past)
    $this->createScheduledTask('test', null, Carbon::now()->subDay(), Carbon::now()); // deleted (should not be fetched)
    $this->createScheduledTask('test', null, Carbon::now()->addDay()); // scheduled in future (should not be fetched)
    $this->createScheduledTask('test', ScheduledTask::STATUS_COMPLETED, Carbon::now()->subDay()); // wrong status (should not be fetched)

    $tasks = $this->repository->findRunningByType('test', 10);
    $this->assertSame($expectedResult, $tasks);
  }

  public function cleanup() {
    $this->truncateEntity(ScheduledTaskEntity::class);
  }

  private function createScheduledTask(string $type, ?string $status, \DateTimeInterface $scheduledAt, \DateTimeInterface $deletedAt = null) {
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
}
