<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\SendingTaskSubscribersCleanup;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber as ScheduledTaskSubscriberFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class SendingTaskSubscribersCleanupTest extends \MailPoetTest {
  /** @var SendingTaskSubscribersCleanup */
  private $worker;

  /** @var SettingsController */
  private $settings;

  /** @var ScheduledTaskFactory */
  private $taskFactory;

  /** @var ScheduledTaskSubscriberFactory */
  private $taskSubscriberFactory;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(SendingTaskSubscribersCleanup::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->taskFactory = new ScheduledTaskFactory();
    $this->taskSubscriberFactory = new ScheduledTaskSubscriberFactory();
    $this->subscriberFactory = new SubscriberFactory();

    $this->settings->set('sending_status_retention_days', '60');
  }

  public function _after() {
    Carbon::setTestNow();
    parent::_after();
  }

  public function testItDeletesOldCompletedTaskSubscribers() {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $subscriber = $this->subscriberFactory->create();
    $oldTask = $this->taskFactory->create(
      'sending',
      ScheduledTaskEntity::STATUS_COMPLETED,
      null,
      null,
      null,
      $now->copy()->subDays(90)
    );
    $this->taskSubscriberFactory->createProcessed($oldTask, $subscriber);

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    $remaining = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findAll();
    verify(count($remaining))->equals(0);

  }

  public function testItKeepsRecentCompletedTaskSubscribers() {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $subscriber = $this->subscriberFactory->create();
    $recentTask = $this->taskFactory->create(
      'sending',
      ScheduledTaskEntity::STATUS_COMPLETED,
      null,
      null,
      null,
      $now->copy()->subDays(10)
    );
    $this->taskSubscriberFactory->createProcessed($recentTask, $subscriber);

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    $remaining = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findAll();
    verify(count($remaining))->equals(1);

  }

  public function testItKeepsNonCompletedTaskSubscribers() {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $subscriber = $this->subscriberFactory->create();
    $scheduledTask = $this->taskFactory->create(
      'sending',
      ScheduledTaskEntity::STATUS_SCHEDULED,
      $now->copy()->subDays(90),
      null,
      null,
      $now->copy()->subDays(90)
    );
    $this->taskSubscriberFactory->createUnprocessed($scheduledTask, $subscriber);

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    $remaining = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findAll();
    verify(count($remaining))->equals(1);

  }

  public function testItSkipsWhenRetentionIsNever() {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $this->settings->set('sending_status_retention_days', '');

    $subscriber = $this->subscriberFactory->create();
    $oldTask = $this->taskFactory->create(
      'sending',
      ScheduledTaskEntity::STATUS_COMPLETED,
      null,
      null,
      null,
      $now->copy()->subDays(365)
    );
    $this->taskSubscriberFactory->createProcessed($oldTask, $subscriber);

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    $remaining = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findAll();
    verify(count($remaining))->equals(1);

  }

  public function testItRespectsBatchSize() {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $oldTask = $this->taskFactory->create(
      'sending',
      ScheduledTaskEntity::STATUS_COMPLETED,
      null,
      null,
      null,
      $now->copy()->subDays(90)
    );

    $total = SendingTaskSubscribersCleanup::ROW_BATCH_SIZE + 5;
    $stsTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $taskId = $oldTask->getId();

    for ($i = 1; $i <= $total; $i++) {
      $this->entityManager->getConnection()->executeStatement(
        "INSERT INTO `{$stsTable}` (task_id, subscriber_id, processed) VALUES (:taskId, :subscriberId, 1)",
        ['taskId' => $taskId, 'subscriberId' => $i]
      );
    }

    $repository = $this->diContainer->get(\MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository::class);
    $deleted = $repository->purgeOldTaskSubscribers(
      60,
      SendingTaskSubscribersCleanup::TASK_BATCH_SIZE,
      SendingTaskSubscribersCleanup::ROW_BATCH_SIZE
    );

    verify($deleted)->equals(SendingTaskSubscribersCleanup::ROW_BATCH_SIZE);

    /** @var string|false $remainingResult */
    $remainingResult = $this->entityManager->getConnection()->executeQuery(
      "SELECT COUNT(*) FROM `{$stsTable}` WHERE task_id = :taskId",
      ['taskId' => $taskId]
    )->fetchOne();
    $remaining = (int)$remainingResult;

    verify($remaining)->equals(5);

  }

  public function testItExcludesSoftDeletedTasks() {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $subscriber = $this->subscriberFactory->create();
    $softDeletedTask = $this->taskFactory->create(
      'sending',
      ScheduledTaskEntity::STATUS_COMPLETED,
      null,
      $now->copy()->subDays(1),
      null,
      $now->copy()->subDays(90)
    );
    $this->taskSubscriberFactory->createProcessed($softDeletedTask, $subscriber);

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    $remaining = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findAll();
    verify(count($remaining))->equals(1);

  }

  public function testItDeletesOnlyEligibleTasksInMixedState() {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $subscriber1 = (new SubscriberFactory())->create();
    $subscriber2 = (new SubscriberFactory())->create();
    $subscriber3 = (new SubscriberFactory())->create();

    $oldCompletedTask = $this->taskFactory->create(
      'sending',
      ScheduledTaskEntity::STATUS_COMPLETED,
      null,
      null,
      null,
      $now->copy()->subDays(90)
    );
    $this->taskSubscriberFactory->createProcessed($oldCompletedTask, $subscriber1);

    $recentCompletedTask = $this->taskFactory->create(
      'sending',
      ScheduledTaskEntity::STATUS_COMPLETED,
      null,
      null,
      null,
      $now->copy()->subDays(10)
    );
    $this->taskSubscriberFactory->createProcessed($recentCompletedTask, $subscriber2);

    $scheduledTask = $this->taskFactory->create(
      'sending',
      ScheduledTaskEntity::STATUS_SCHEDULED,
      $now->copy()->subDays(90),
      null,
      null,
      $now->copy()->subDays(90)
    );
    $this->taskSubscriberFactory->createUnprocessed($scheduledTask, $subscriber3);

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    $remaining = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findAll();
    verify(count($remaining))->equals(2);

  }

  public function testItProcessesMultipleBatchesAcrossIterations() {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $stsTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();

    // Create more eligible tasks than TASK_BATCH_SIZE (200), each with 1 subscriber
    $taskCount = SendingTaskSubscribersCleanup::TASK_BATCH_SIZE + 50;
    for ($i = 0; $i < $taskCount; $i++) {
      $oldTask = $this->taskFactory->create(
        'sending',
        ScheduledTaskEntity::STATUS_COMPLETED,
        null,
        null,
        null,
        $now->copy()->subDays(90)
      );
      $this->entityManager->getConnection()->executeStatement(
        "INSERT INTO `{$stsTable}` (task_id, subscriber_id, processed) VALUES (:taskId, :subscriberId, 1)",
        ['taskId' => $oldTask->getId(), 'subscriberId' => $i + 1]
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

  public function testItSchedulesInTheFuture() {
    $nextRunDate = $this->worker->getNextRunDate();
    verify($nextRunDate)->notNull();
    verify($nextRunDate->getTimestamp())->greaterThan(Carbon::now()->getTimestamp());

    $tomorrow = Carbon::now()->addDay();
    verify($nextRunDate->getTimestamp())->lessThan($tomorrow->getTimestamp());
  }
}
