<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\Bounce;
use MailPoet\Cron\Workers\BounceTaskSubscribersCleanup;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber as ScheduledTaskSubscriberFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class BounceTaskSubscribersCleanupTest extends \MailPoetTest {
  /** @var BounceTaskSubscribersCleanup */
  private $worker;

  /** @var ScheduledTaskFactory */
  private $taskFactory;

  /** @var ScheduledTaskSubscriberFactory */
  private $taskSubscriberFactory;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(BounceTaskSubscribersCleanup::class);
    $this->taskFactory = new ScheduledTaskFactory();
    $this->taskSubscriberFactory = new ScheduledTaskSubscriberFactory();
    $this->subscriberFactory = new SubscriberFactory();
  }

  public function _after() {
    Carbon::setTestNow();
    parent::_after();
  }

  public function testItDeletesCompletedBounceTaskSubscribers() {
    $subscriber = $this->subscriberFactory->create();
    $completedTask = $this->taskFactory->create(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);
    $this->taskSubscriberFactory->createProcessed($completedTask, $subscriber);

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    $remaining = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findAll();
    verify(count($remaining))->equals(0);
  }

  public function testItKeepsNonCompletedBounceTaskSubscribers() {
    $subscriber = $this->subscriberFactory->create();
    $scheduledTask = $this->taskFactory->create(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->taskSubscriberFactory->createUnprocessed($scheduledTask, $subscriber);

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    $remaining = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findAll();
    verify(count($remaining))->equals(1);
  }

  public function testItKeepsNonBounceTaskSubscribers() {
    $subscriber = $this->subscriberFactory->create();
    $sendingTask = $this->taskFactory->create('sending', ScheduledTaskEntity::STATUS_COMPLETED);
    $this->taskSubscriberFactory->createProcessed($sendingTask, $subscriber);

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    $remaining = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findAll();
    verify(count($remaining))->equals(1);
  }

  public function testItExcludesSoftDeletedTasks() {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $subscriber = $this->subscriberFactory->create();
    $softDeletedTask = $this->taskFactory->create(
      Bounce::TASK_TYPE,
      ScheduledTaskEntity::STATUS_COMPLETED,
      null,
      $now->copy()->subDays(1)
    );
    $this->taskSubscriberFactory->createProcessed($softDeletedTask, $subscriber);

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    $remaining = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findAll();
    verify(count($remaining))->equals(1);
  }

  public function testItDeletesOnlyEligibleTasksInMixedState() {
    $subscriber1 = (new SubscriberFactory())->create();
    $subscriber2 = (new SubscriberFactory())->create();
    $subscriber3 = (new SubscriberFactory())->create();

    $completedBounce = $this->taskFactory->create(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);
    $this->taskSubscriberFactory->createProcessed($completedBounce, $subscriber1);

    $scheduledBounce = $this->taskFactory->create(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->taskSubscriberFactory->createUnprocessed($scheduledBounce, $subscriber2);

    $completedSending = $this->taskFactory->create('sending', ScheduledTaskEntity::STATUS_COMPLETED);
    $this->taskSubscriberFactory->createProcessed($completedSending, $subscriber3);

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    $remaining = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findAll();
    verify(count($remaining))->equals(2);
  }

  public function testItRespectsRowBatchSize() {
    $completedTask = $this->taskFactory->create(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);

    $total = BounceTaskSubscribersCleanup::ROW_BATCH_SIZE + 5;
    $stsTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $taskId = $completedTask->getId();

    for ($i = 1; $i <= $total; $i++) {
      $this->entityManager->getConnection()->executeStatement(
        "INSERT INTO `{$stsTable}` (task_id, subscriber_id, processed) VALUES (:taskId, :subscriberId, 1)",
        ['taskId' => $taskId, 'subscriberId' => $i]
      );
    }

    $repository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
    $deleted = $repository->purgeCompletedBounceTaskSubscribers(
      BounceTaskSubscribersCleanup::TASK_BATCH_SIZE,
      BounceTaskSubscribersCleanup::ROW_BATCH_SIZE
    );

    verify($deleted)->equals(BounceTaskSubscribersCleanup::ROW_BATCH_SIZE);

    /** @var string|false $remainingResult */
    $remainingResult = $this->entityManager->getConnection()->executeQuery(
      "SELECT COUNT(*) FROM `{$stsTable}` WHERE task_id = :taskId",
      ['taskId' => $taskId]
    )->fetchOne();

    verify((int)$remainingResult)->equals(5);
  }

  public function testItProcessesMultipleBatchesAcrossIterations() {
    $stsTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();

    // Create more eligible tasks than TASK_BATCH_SIZE (200), each with 1 subscriber
    $taskCount = BounceTaskSubscribersCleanup::TASK_BATCH_SIZE + 50;
    for ($i = 0; $i < $taskCount; $i++) {
      $completedTask = $this->taskFactory->create(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);
      $this->entityManager->getConnection()->executeStatement(
        "INSERT INTO `{$stsTable}` (task_id, subscriber_id, processed) VALUES (:taskId, :subscriberId, 1)",
        ['taskId' => $completedTask->getId(), 'subscriberId' => $i + 1]
      );
    }

    /** @var string|false $beforeResult */
    $beforeResult = $this->entityManager->getConnection()->executeQuery(
      "SELECT COUNT(*) FROM `{$stsTable}`"
    )->fetchOne();
    verify((int)$beforeResult)->equals($taskCount);

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    /** @var string|false $afterResult */
    $afterResult = $this->entityManager->getConnection()->executeQuery(
      "SELECT COUNT(*) FROM `{$stsTable}`"
    )->fetchOne();
    verify((int)$afterResult)->equals(0);
  }

  public function testItSchedulesWithinTheNextHour() {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $nextRunDate = $this->worker->getNextRunDate();
    verify($nextRunDate)->notNull();
    verify($nextRunDate->getTimestamp())->greaterThan($now->getTimestamp());

    $nextHour = $now->copy()->startOfHour()->addHour();
    verify($nextRunDate->getTimestamp())->greaterThanOrEqual($nextHour->getTimestamp());
    verify($nextRunDate->getTimestamp())->lessThan($nextHour->copy()->addHour()->getTimestamp());
  }
}
