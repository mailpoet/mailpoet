<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Cron\Workers\BulkConfirmationEmailResend;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\MailerLog;
use MailPoet\Mailer\MigrationSendingPauser;
use MailPoet\Newsletter\Sending\ScheduledTaskQueuedSubscriberRepository;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber as ScheduledTaskSubscriberFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260617_130000_App_Test extends \MailPoetTest {
  /** @var Migration_20260617_130000_App */
  private $migration;

  /** @var ScheduledTaskQueuedSubscriberRepository */
  private $queueRepository;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260617_130000_App($this->diContainer);
    $this->queueRepository = $this->diContainer->get(ScheduledTaskQueuedSubscriberRepository::class);
    MailerLog::resumeSending(); // start from a clean, active mailer log
  }

  public function testItMovesPendingRowsOfInFlightSendingTasksToTheQueue(): void {
    $subscriber1 = $this->createSubscriber();
    $subscriber2 = $this->createSubscriber();
    $task = $this->createTask(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    // simulate pre-migration state: pending rows still in the log table
    $this->createPendingLogSubscriber($task, $subscriber1);
    $this->createPendingLogSubscriber($task, $subscriber2);

    $this->migration->run();

    // pending rows moved into the queue and removed from the log
    verify($this->queueRepository->countForTask($task))->equals(2);
    verify($this->countPendingLogSubscribers($task))->equals(0);
    verify($this->queueRepository->getSubscriberIdsBatchForTask((int)$task->getId(), 0, 10))
      ->equals([(int)$subscriber1->getId(), (int)$subscriber2->getId()]);
  }

  public function testItMovesPendingRowsOfInFlightConfirmationTasksToTheQueue(): void {
    $subscriber = $this->createSubscriber();
    $task = $this->createTask(BulkConfirmationEmailResend::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    // simulate pre-migration state: pending rows still in the log table
    $this->createPendingLogSubscriber($task, $subscriber);

    $this->migration->run();

    verify($this->queueRepository->countForTask($task))->equals(1);
    verify($this->countPendingLogSubscribers($task))->equals(0);
  }

  public function testItLeavesCompletedSendingTasksUntouched(): void {
    $subscriber = $this->createSubscriber();
    $task = $this->createTask(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);
    $this->createPendingLogSubscriber($task, $subscriber);

    $this->migration->run();

    verify($this->queueRepository->countForTask($task))->equals(0);
    verify($this->countPendingLogSubscribers($task))->equals(1);
  }

  public function testItLeavesNonSendingTasksUntouched(): void {
    $subscriber = $this->createSubscriber();
    $task = $this->createTask('bounce', null);
    $this->createPendingLogSubscriber($task, $subscriber);

    $this->migration->run();

    verify($this->queueRepository->countForTask($task))->equals(0);
    verify($this->countPendingLogSubscribers($task))->equals(1);
  }

  public function testItIsIdempotent(): void {
    $subscriber = $this->createSubscriber();
    $task = $this->createTask(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->createPendingLogSubscriber($task, $subscriber);

    $this->migration->run();
    $this->migration->run();

    verify($this->queueRepository->countForTask($task))->equals(1);
    verify($this->countPendingLogSubscribers($task))->equals(0);
  }

  public function testItRestoresActiveSendingAfterRunning(): void {
    $subscriber = $this->createSubscriber();
    $task = $this->createTask(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->createPendingLogSubscriber($task, $subscriber);

    $this->migration->run();

    verify(MailerLog::isSendingPaused())->false();
  }

  public function testItRestoresMigrationPausedSendingWhenNoTasksNeedBackfill(): void {
    $this->diContainer->get(MigrationSendingPauser::class)->pause();

    $this->migration->run();

    verify(MailerLog::isSendingPaused())->false();
  }

  private function createTask(string $type, ?string $status): ScheduledTaskEntity {
    return (new ScheduledTaskFactory())->create($type, $status, Carbon::now()->subDay());
  }

  private function createSubscriber(): SubscriberEntity {
    return (new SubscriberFactory())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
  }

  private function createPendingLogSubscriber(ScheduledTaskEntity $task, SubscriberEntity $subscriber): void {
    (new ScheduledTaskSubscriberFactory())->createUnprocessed($task, $subscriber);
  }

  private function countPendingLogSubscribers(ScheduledTaskEntity $task): int {
    return $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->count([
      'task' => $task,
      'processed' => ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
    ]);
  }
}
