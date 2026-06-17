<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\CliCommands;

use InvalidArgumentException;
use MailPoet\Cron\CliCommands\TaskCanceller;
use MailPoet\Cron\Workers\Bounce;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoetVendor\Carbon\Carbon;

class TaskCancellerTest extends \MailPoetTest {
  /** @var TaskCanceller */
  private $canceller;

  /** @var ScheduledTaskFactory */
  private $taskFactory;

  public function _before() {
    parent::_before();
    $this->canceller = $this->diContainer->get(TaskCanceller::class);
    $this->taskFactory = new ScheduledTaskFactory();
  }

  public function testItCancelsAScheduledTask() {
    $task = $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);

    $result = $this->canceller->cancel((int)$task->getId());

    verify($result['id'])->equals($task->getId());
    verify($result['type'])->same(Bounce::TASK_TYPE);
    $this->entityManager->refresh($task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_CANCELLED);
    verify($task->getCancelledAt())->notNull();
  }

  public function testItCancelsAPausedTask() {
    $task = $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_PAUSED);

    $result = $this->canceller->cancel((int)$task->getId());

    verify($result['id'])->equals($task->getId());
    $this->entityManager->refresh($task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_CANCELLED);
  }

  public function testItCancelsACliTask() {
    // Recovery path for a zombie left by a hard-killed CLI run: cancel, then re-add.
    $task = $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_CLI);

    $result = $this->canceller->cancel((int)$task->getId());

    verify($result['id'])->equals($task->getId());
    $this->entityManager->refresh($task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_CANCELLED);
    verify($task->getCancelledAt())->notNull();
  }

  public function testItThrowsWhenCancellingARunningTask() {
    $task = $this->createTask(Bounce::TASK_TYPE, null);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/running.*cannot be cancelled/');
    $this->canceller->cancel((int)$task->getId());
  }

  public function testItThrowsWhenCancellingACompletedTask() {
    $task = $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/cannot be cancelled/');
    $this->canceller->cancel((int)$task->getId());
  }

  public function testItThrowsWhenCancellingAnAlreadyCancelledTask() {
    $task = $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_CANCELLED);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/cannot be cancelled/');
    $this->canceller->cancel((int)$task->getId());
  }

  public function testItThrowsWhenCancellingAnInvalidTask() {
    $task = $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_INVALID);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/cannot be cancelled/');
    $this->canceller->cancel((int)$task->getId());
  }

  public function testItThrowsWhenTaskIdDoesNotExist() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/No task with ID/');
    $this->canceller->cancel(999999);
  }

  public function testItThrowsWhenTaskIsSoftDeleted() {
    $task = $this->taskFactory->create(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now(), Carbon::now());

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/No task with ID/');
    $this->canceller->cancel((int)$task->getId());
  }

  private function createTask(string $type, ?string $status): ScheduledTaskEntity {
    return $this->taskFactory->create($type, $status, Carbon::now());
  }
}
