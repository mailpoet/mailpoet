<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\CliCommands;

use InvalidArgumentException;
use MailPoet\Cron\CliCommands\TaskTrigger;
use MailPoet\Cron\Workers\Bounce;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoetVendor\Carbon\Carbon;

class TaskTriggerTest extends \MailPoetTest {
  /** @var TaskTrigger */
  private $trigger;

  /** @var ScheduledTaskFactory */
  private $taskFactory;

  public function _before() {
    parent::_before();
    $this->trigger = $this->diContainer->get(TaskTrigger::class);
    $this->taskFactory = new ScheduledTaskFactory();
  }

  public function testItTriggersTheNextScheduledTaskByType() {
    $task = $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDays(5));

    $result = $this->trigger->trigger(Bounce::TASK_TYPE);

    verify($result['id'])->equals($task->getId());
    verify($result['type'])->same(Bounce::TASK_TYPE);

    $this->entityManager->refresh($task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->assertIsDueNow($task);
  }

  public function testItTriggersTheSoonestDueTaskWhenSeveralOfTheSameTypeAreScheduled() {
    $soonest = $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDays(1));
    $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDays(5));

    $result = $this->trigger->trigger(Bounce::TASK_TYPE);

    verify($result['id'])->equals($soonest->getId());
  }

  public function testItThrowsWhenNoScheduledTaskOfTypeExists() {
    $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/no scheduled task of type/i');
    $this->trigger->trigger(Bounce::TASK_TYPE);
  }

  public function testItTriggersAScheduledTaskById() {
    $task = $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDays(5));

    $result = $this->trigger->trigger(Bounce::TASK_TYPE, (int)$task->getId());

    verify($result['id'])->equals($task->getId());
    $this->entityManager->refresh($task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->assertIsDueNow($task);
  }

  public function testItTriggersAPausedTaskByIdAndReschedulesIt() {
    $task = $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_PAUSED, Carbon::now()->addDays(5));

    $result = $this->trigger->trigger(Bounce::TASK_TYPE, (int)$task->getId());

    verify($result['id'])->equals($task->getId());
    $this->entityManager->refresh($task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->assertIsDueNow($task);
  }

  public function testItThrowsWhenTaskIdTypeDoesNotMatch() {
    $task = $this->createTask('woocommerce_sync', ScheduledTaskEntity::STATUS_SCHEDULED);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/not \'bounce\'/');
    $this->trigger->trigger(Bounce::TASK_TYPE, (int)$task->getId());
  }

  public function testItThrowsWhenTaskIdDoesNotExist() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/No task with ID/');
    $this->trigger->trigger(Bounce::TASK_TYPE, 999999);
  }

  public function testItThrowsWhenTaskIsSoftDeleted() {
    $task = $this->taskFactory->create(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now(), Carbon::now());

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/No task with ID/');
    $this->trigger->trigger(Bounce::TASK_TYPE, (int)$task->getId());
  }

  public function testItThrowsWhenTriggeringACompletedTaskById() {
    $task = $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/cannot be triggered/');
    $this->trigger->trigger(Bounce::TASK_TYPE, (int)$task->getId());
  }

  public function testItThrowsWhenTriggeringACancelledTaskById() {
    $task = $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_CANCELLED);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/cannot be triggered/');
    $this->trigger->trigger(Bounce::TASK_TYPE, (int)$task->getId());
  }

  public function testItThrowsWhenTriggeringARunningTaskById() {
    $task = $this->createTask(Bounce::TASK_TYPE, null);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/running.*cannot be triggered/');
    $this->trigger->trigger(Bounce::TASK_TYPE, (int)$task->getId());
  }

  public function testItThrowsWhenTriggeringACliTaskById() {
    $task = $this->createTask(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_CLI);

    $this->expectException(InvalidArgumentException::class);
    // The raw 'cli' status is surfaced in the message (nameStatus only maps the NULL placeholder).
    $this->expectExceptionMessageMatches('/\'cli\'.*cannot be triggered/');
    $this->trigger->trigger(Bounce::TASK_TYPE, (int)$task->getId());
  }

  public function testItThrowsOnUnknownTypeListingValidTypes() {
    try {
      $this->trigger->trigger('totally_bogus_type');
      $this->fail('Expected an InvalidArgumentException.');
    } catch (InvalidArgumentException $e) {
      verify($e->getMessage())->stringContainsString("Unknown task type 'totally_bogus_type'");
      verify($e->getMessage())->stringContainsString('sending');
      verify($e->getMessage())->stringContainsString(Bounce::TASK_TYPE);
    }
  }

  private function assertIsDueNow(ScheduledTaskEntity $task): void {
    $scheduledAt = $task->getScheduledAt();
    if ($scheduledAt === null) {
      $this->fail('Expected the triggered task to have a scheduled_at timestamp.');
    }
    $diff = abs(Carbon::now()->getTimestamp() - $scheduledAt->getTimestamp());
    verify($diff <= 120)->true();
  }

  private function createTask(string $type, ?string $status, ?Carbon $scheduledAt = null): ScheduledTaskEntity {
    return $this->taskFactory->create($type, $status, $scheduledAt ?? Carbon::now());
  }
}
