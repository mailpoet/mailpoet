<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use MailPoet\Cron\Workers\SendingQueue\Migration;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class MigrationTest extends \MailPoetTest {
  /** @var bool */
  public $altered = false;
  /** @var SendingQueueEntity */
  public $queueScheduled;
  /** @var SendingQueueEntity */
  public $queueCompleted;
  /** @var SendingQueueEntity */
  public $queuePaused;
  /** @var SendingQueueEntity */
  public $queueRunning;
  /** @var SubscriberEntity */
  public $subscriberToProcess;
  /** @var SubscriberEntity */
  public $subscriberProcessed;
  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;
  /** @var Migration */
  private $worker;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  public function _before() {
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->scheduledTaskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);

    parent::_before();
    // Alter table to test migration
    $this->downgradeTable();

    $this->subscriberToProcess = (new SubscriberFactory())
      ->withEmail('to_process@example.com')
      ->create();
    $this->subscriberProcessed = (new SubscriberFactory())
      ->withEmail('processed@example.com')
      ->create();

    // subscribers should be migrated
    $this->queueRunning = $this->createSendingQueue();
    $this->queuePaused = $this->createSendingQueue(SendingQueueEntity::STATUS_PAUSED);

    // subscribers should not be migrated
    $this->queueCompleted = $this->createSendingQueue(SendingQueueEntity::STATUS_COMPLETED);
    $this->queueScheduled = $this->createSendingQueue(SendingQueueEntity::STATUS_SCHEDULED);

    $this->worker = $this->diContainer->get(Migration::class);
  }

  public function testItDefinesConstants() {
    expect(Migration::BATCH_SIZE)->equals(20);
  }

  public function testItChecksForACompletedMigrationBeforeRunning() {
    expect($this->worker->checkProcessingRequirements())->true();
    $this->createCompletedTask();
    expect($this->worker->checkProcessingRequirements())->false();
  }

  public function testItPausesSendingWhenPreparingATask() {
    $task = $this->createScheduledTask();
    expect(MailerLog::isSendingPaused())->false();
    $result = $this->worker->prepareTaskStrategy($task, microtime(true));
    expect($result)->true();
    expect(MailerLog::isSendingPaused())->true();
  }

  public function testItResumesSendingIfThereIsNothingToMigrate() {
    $this->truncateEntity(SendingQueueEntity::class);
    $this->worker->pauseSending();
    expect(MailerLog::isSendingPaused())->true();
    $task = $this->createScheduledTask();
    $this->worker->prepareTaskStrategy($task, microtime(true));
    expect(MailerLog::isSendingPaused())->false();
  }

  public function testItCompletesTaskIfThereIsNothingToMigrate() {
    $this->truncateEntity(SendingQueueEntity::class);
    $task = $this->createScheduledTask();
    $this->worker->prepareTaskStrategy($task, microtime(true));
    $task = $this->scheduledTasksRepository->findOneById($task->getId());
    assert($task instanceof ScheduledTaskEntity);
    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_COMPLETED);
  }

  public function testItMigratesSendingQueuesAndSubscribers() {
    expect($this->worker->getUnmigratedQueueIds())->count(4);
    $tasks = $this->scheduledTasksRepository->findBy(['type' => SendingTask::TASK_TYPE]);
    expect($tasks)->count(0);
    expect($this->scheduledTaskSubscribersRepository->countBy([]))->equals(0);

    $task = $this->createRunningTask();
    $this->worker->processTaskStrategy($task, microtime(true));

    expect($this->worker->getUnmigratedQueueIds())->count(0);
    $tasks = $this->scheduledTasksRepository->findBy(['type' => SendingTask::TASK_TYPE]);
    expect($tasks)->count(4);
    expect($this->scheduledTaskSubscribersRepository->countBy([]))->equals(4); // 2 for running, 2 for paused

    $this->entityManager->clear();
    $queue = $this->sendingQueuesRepository->findOneById($this->queueRunning->getId());
     if (!$queue) throw new InvalidStateException();
    $task = $this->scheduledTasksRepository->findOneBy(['id' => $queue->getTask()]);
    if (!$task) throw new InvalidStateException();
    expect($task->getType())->equals(SendingTask::TASK_TYPE);

    $migratedSubscribers = $this->scheduledTaskSubscribersRepository->findBy(['task' => $task], ['subscriber' => 'asc']);
    expect($migratedSubscribers)->count(2);
    expect($migratedSubscribers[0]->getProcessed())->equals(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED);
    expect($migratedSubscribers[1]->getProcessed())->equals(ScheduledTaskSubscriberEntity::STATUS_PROCESSED);
  }

  public function testItResumesSendingAfterMigratingSendingQueuesAndSubscribers() {
    $this->worker->pauseSending();
    expect(MailerLog::isSendingPaused())->true();
    $task = $this->createRunningTask();
    $this->worker->processTaskStrategy($task, microtime(true));
    expect(MailerLog::isSendingPaused())->false();
  }

  public function testItUsesWPTimeToReturnNextRunDate() {
    $timestamp = 1514801410;

    $wp = Stub::make(new WPFunctions, [
      'currentTime' => function($time) use($timestamp) {
        // "timestamp" string is passed as an argument
        expect($time)->equals('timestamp');
        return $timestamp;
      },
    ]);

    $nextRunDate = $this->worker->getNextRunDate($wp);
    expect($nextRunDate->getTimestamp())->equals($timestamp);
  }

  private function createScheduledTask() {
    return $this->scheduledTaskFactory->create(
      Migration::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))
    );
  }

  private function createRunningTask() {
    return $this->scheduledTaskFactory->create(
      Migration::TASK_TYPE,
      null,
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))
    );
  }

  private function createCompletedTask() {
    return $this->scheduledTaskFactory->create(
      Migration::TASK_TYPE,
      ScheduledTaskEntity::STATUS_COMPLETED,
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))
    );
  }

  private function createSendingQueue($status = null): SendingQueueEntity {
    $task = $this->entityManager->getReference(ScheduledTaskEntity::class, 0);
    if (!$task) throw new InvalidStateException();
    $newsletter = $this->entityManager->getReference(NewsletterEntity::class, 0);
    if (!$newsletter) throw new InvalidStateException();

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $queue->setSubscribers(serialize([
      'to_process' => [$this->subscriberToProcess->getId()],
      'processed' => [$this->subscriberProcessed->getId()],
    ]));
    $queue->setCountTotal(2);
    $queue->setCountProcessed(1);
    $queue->setCountToProcess(1);
    $this->sendingQueuesRepository->persist($queue);
    $this->sendingQueuesRepository->flush();

    if ($status) {
      $sendingQueueTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
      $this->entityManager->getConnection()->executeQuery(
        "UPDATE {$sendingQueueTable} " .
        "SET status = '{$status}' " .
        "WHERE id = {$queue->getId()}"
      );
    }
    return $queue;
  }

  private function downgradeTable() {
    global $wpdb;
    $sendingQueueTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $wpdb->query(
      'ALTER TABLE ' . $sendingQueueTable . ' ' .
      'ADD COLUMN `type` varchar(90) NULL DEFAULT NULL,' .
      'ADD COLUMN `status` varchar(12) NULL DEFAULT NULL,' .
      'ADD COLUMN `priority` mediumint(9) NOT NULL DEFAULT 0,' .
      'ADD COLUMN `scheduled_at` TIMESTAMP NULL,' .
      'ADD COLUMN `processed_at` TIMESTAMP NULL'
    );
  }

  private function restoreTable() {
    global $wpdb;
    $sendingQueueTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $wpdb->query(
      'ALTER TABLE ' . $sendingQueueTable . ' ' .
      'DROP COLUMN `type`,' .
      'DROP COLUMN `status`,' .
      'DROP COLUMN `priority`,' .
      'DROP COLUMN `scheduled_at`,' .
      'DROP COLUMN `processed_at`'
    );
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(SubscriberEntity::class);

    // Restore table after testing
    $this->restoreTable();
    $this->altered = false;
  }
}
