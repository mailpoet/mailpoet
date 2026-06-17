<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\CliCommands;

use InvalidArgumentException;
use MailPoet\Cron\CliCommands\TaskAdder;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\StatsNotifications\Worker as StatsNotificationsWorker;
use MailPoet\Cron\Workers\SubscribersCountCacheRecalculation;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoetVendor\Carbon\Carbon;

class TaskAdderTest extends \MailPoetTest {
  /** @var TaskAdder */
  private $adder;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var ScheduledTaskFactory */
  private $taskFactory;

  public function _before() {
    parent::_before();
    $this->adder = $this->diContainer->get(TaskAdder::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->taskFactory = new ScheduledTaskFactory();
  }

  public function testItAddsADueNowLowPriorityScheduledTaskByDefault() {
    $before = Carbon::now()->subSecond();

    $result = $this->adder->add(UnsubscribeTokens::TASK_TYPE, null, null, 'low', false, false);

    verify($result['action'])->same('created');
    verify($result['id'])->greaterThan(0);

    $task = $this->scheduledTasksRepository->findOneById($result['id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
    verify($task->getPriority())->same(ScheduledTaskEntity::PRIORITY_LOW);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    verify($scheduledAt->getTimestamp())->greaterThanOrEqual($before->getTimestamp());
  }

  public function testItRespectsPriority() {
    $result = $this->adder->add(UnsubscribeTokens::TASK_TYPE, null, null, 'high', false, false);

    $task = $this->scheduledTasksRepository->findOneById($result['id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getPriority())->same(ScheduledTaskEntity::PRIORITY_HIGH);
  }

  public function testItRespectsAtDatetime() {
    $result = $this->adder->add(UnsubscribeTokens::TASK_TYPE, '2030-01-01 09:00:00', null, 'low', false, false);

    $task = $this->scheduledTasksRepository->findOneById($result['id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    verify($scheduledAt->format('Y-m-d H:i:s'))->same('2030-01-01 09:00:00');
  }

  public function testItRespectsInSeconds() {
    $result = $this->adder->add(UnsubscribeTokens::TASK_TYPE, null, 3600, 'low', false, false);

    $task = $this->scheduledTasksRepository->findOneById($result['id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    $expected = Carbon::now()->addSeconds(3600);
    // Within a minute of "now + 3600s", tolerant of test execution time.
    verify(abs($scheduledAt->getTimestamp() - $expected->getTimestamp()))->lessThan(60);
  }

  public function testItRejectsAtAndInTogether() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/--at and --in/');
    $this->adder->add(UnsubscribeTokens::TASK_TYPE, '2030-01-01', 60, 'low', false, false);
  }

  public function testItRejectsUnparseableAt() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/Could not parse --at/');
    $this->adder->add(UnsubscribeTokens::TASK_TYPE, 'not a date at all', null, 'low', false, false);
  }

  public function testItRejectsInvalidPriority() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/Invalid priority/');
    $this->adder->add(UnsubscribeTokens::TASK_TYPE, null, null, 'urgent', false, false);
  }

  public function testItRejectsSendingAsMailingType() {
    try {
      $this->adder->add(SendingQueueWorker::TASK_TYPE, null, null, 'low', false, false);
      $this->fail('Expected an InvalidArgumentException.');
    } catch (InvalidArgumentException $e) {
      verify($e->getMessage())->stringContainsString('mailing type');
      verify($e->getMessage())->stringContainsString(UnsubscribeTokens::TASK_TYPE);
    }
  }

  public function testItRejectsStatsNotificationAsMailingType() {
    try {
      $this->adder->add(StatsNotificationsWorker::TASK_TYPE, null, null, 'low', false, false);
      $this->fail('Expected an InvalidArgumentException.');
    } catch (InvalidArgumentException $e) {
      verify($e->getMessage())->stringContainsString('mailing type');
    }
  }

  public function testItRejectsUnknownTypeListingAddableTypes() {
    try {
      $this->adder->add('totally_bogus_type', null, null, 'low', false, false);
      $this->fail('Expected an InvalidArgumentException.');
    } catch (InvalidArgumentException $e) {
      verify($e->getMessage())->stringContainsString("Unknown task type 'totally_bogus_type'");
      verify($e->getMessage())->stringContainsString(UnsubscribeTokens::TASK_TYPE);
    }
  }

  public function testItReportsAnExistingScheduledTaskWithoutCreating() {
    $existing = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());

    $result = $this->adder->add(UnsubscribeTokens::TASK_TYPE, null, null, 'low', false, false);

    verify($result['action'])->same('duplicate');
    verify($result['id'])->same((int)$existing->getId());
    verify($result['message'])->stringContainsString('already scheduled');

    $all = $this->scheduledTasksRepository->findBy(['type' => UnsubscribeTokens::TASK_TYPE]);
    verify($all)->arrayCount(1);
  }

  public function testItForcesADuplicateWithForce() {
    $existing = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());

    $result = $this->adder->add(UnsubscribeTokens::TASK_TYPE, null, null, 'low', true, false);

    verify($result['action'])->same('created');
    verify($result['id'])->notEquals((int)$existing->getId());

    $all = $this->scheduledTasksRepository->findBy(['type' => UnsubscribeTokens::TASK_TYPE]);
    verify($all)->arrayCount(2);
  }

  public function testItClaimsAndRunsToCompletion() {
    $result = $this->adder->add(SubscribersCountCacheRecalculation::TASK_TYPE, null, null, 'low', false, true);

    verify($result['action'])->same('claimed');
    $this->assertIsArray($result['run']);
    verify($result['run']['completed'])->true();

    $this->entityManager->clear();
    $task = $this->scheduledTasksRepository->findOneById($result['id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);
    verify($task->getProcessedAt())->notNull();
    // meta.cli is stamped on completion as the permanent "done by CLI" breadcrumb.
    $meta = $task->getMeta();
    $this->assertIsArray($meta);
    $this->assertArrayHasKey('cli', $meta);
    verify($meta['cli']['pid'])->same(getmypid());
    $this->assertArrayHasKey('started_at', $meta['cli']);
  }

  public function testItIgnoresAnExistingScheduledTaskWhenRunning() {
    // A scheduled duplicate must not block --run: the claim is an independent row.
    $existing = $this->taskFactory->create(SubscribersCountCacheRecalculation::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());

    $result = $this->adder->add(SubscribersCountCacheRecalculation::TASK_TYPE, null, null, 'low', false, true);

    verify($result['action'])->same('claimed');
    verify($result['id'])->notEquals((int)$existing->getId());
  }

  public function testItRejectsRunCombinedWithAt() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/--run cannot be combined/');
    $this->adder->add(UnsubscribeTokens::TASK_TYPE, '2030-01-01', null, 'low', false, true);
  }

  public function testItRejectsRunCombinedWithIn() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/--run cannot be combined/');
    $this->adder->add(UnsubscribeTokens::TASK_TYPE, null, 60, 'low', false, true);
  }
}
