<?php

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use MailPoet\Cron\Workers\SendingQueue\Migration;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class MigrationTest extends \MailPoetTest {
  public $altered;
  public $queueScheduled;
  public $queueCompleted;
  public $queuePaused;
  public $queueRunning;
  public $subscriberProcessed;
  public $subscriberToProcess;
  /** @var Migration */
  private $worker;

  public function _before() {
    parent::_before();
    // Alter table to test migration
    $this->downgradeTable();

    $this->subscriberToProcess = Subscriber::createOrUpdate([
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'email' => 'to_process@example.com',
    ]);
    $this->subscriberProcessed = Subscriber::createOrUpdate([
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'email' => 'processed@example.com',
    ]);

    // subscribers should be migrated
    $this->queueRunning = $this->createSendingQueue();
    $this->queuePaused = $this->createSendingQueue(SendingQueue::STATUS_PAUSED);

    // subscribers should not be migrated
    $this->queueCompleted = $this->createSendingQueue(SendingQueue::STATUS_COMPLETED);
    $this->queueScheduled = $this->createSendingQueue(SendingQueue::STATUS_SCHEDULED);

    $this->worker = new Migration();
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
    SendingQueue::deleteMany();
    $this->worker->pauseSending();
    expect(MailerLog::isSendingPaused())->true();
    $task = $this->createScheduledTask();
    $this->worker->prepareTaskStrategy($task, microtime(true));
    expect(MailerLog::isSendingPaused())->false();
  }

  public function testItCompletesTaskIfThereIsNothingToMigrate() {
    SendingQueue::deleteMany();
    $task = $this->createScheduledTask();
    $this->worker->prepareTaskStrategy($task, microtime(true));
    $task = ScheduledTask::findOne($task->id);
    assert($task instanceof ScheduledTask);
    expect($task->status)->equals(ScheduledTask::STATUS_COMPLETED);
  }

  public function testItMigratesSendingQueuesAndSubscribers() {
    expect($this->worker->getUnmigratedQueues()->count())->equals(4);
    expect(ScheduledTask::where('type', SendingTask::TASK_TYPE)->findMany())->count(0);
    expect(ScheduledTaskSubscriber::whereGt('task_id', 0)->count())->equals(0);

    $task = $this->createRunningTask();
    $this->worker->processTaskStrategy($task, microtime(true));

    expect($this->worker->getUnmigratedQueues()->count())->equals(0);
    expect(ScheduledTask::where('type', SendingTask::TASK_TYPE)->findMany())->count(4);
    expect(ScheduledTaskSubscriber::whereGt('task_id', 0)->count())->equals(4); // 2 for running, 2 for paused

    $queue = SendingQueue::findOne($this->queueRunning->id);
    assert($queue instanceof SendingQueue);
    $task = ScheduledTask::findOne($queue->taskId);
    assert($task instanceof ScheduledTask);
    expect($task->type)->equals(SendingTask::TASK_TYPE);

    $migratedSubscribers = ScheduledTaskSubscriber::where('task_id', $queue->taskId)
      ->orderByAsc('subscriber_id')
      ->findMany();
    expect($migratedSubscribers)->count(2);
    expect($migratedSubscribers[0]->processed)->equals(ScheduledTaskSubscriber::STATUS_UNPROCESSED);
    expect($migratedSubscribers[1]->processed)->equals(ScheduledTaskSubscriber::STATUS_PROCESSED);
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
    $task = ScheduledTask::create();
    $task->type = Migration::TASK_TYPE;
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->save();
    return $task;
  }

  private function createRunningTask() {
    $task = ScheduledTask::create();
    $task->type = Migration::TASK_TYPE;
    $task->status = null;
    $task->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->save();
    return $task;
  }

  private function createCompletedTask() {
    $task = ScheduledTask::create();
    $task->type = Migration::TASK_TYPE;
    $task->status = ScheduledTask::STATUS_COMPLETED;
    $task->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->save();
    return $task;
  }

  private function createSendingQueue($status = null) {
    $queue = SendingQueue::create();
    $queue->newsletterId = 0;
    $queue->taskId = 0;
    $queue->subscribers = serialize([
      'to_process' => [$this->subscriberToProcess->id],
      'processed' => [$this->subscriberProcessed->id],
    ]);
    $queue->countTotal = 2;
    $queue->countProcessed = 1;
    $queue->countToProcess = 1;
    $queue->status = $status;
    return $queue->save();
  }

  private function downgradeTable() {
    global $wpdb;
    $wpdb->query(
      'ALTER TABLE ' . SendingQueue::$_table . ' ' .
      'ADD COLUMN `type` varchar(90) NULL DEFAULT NULL,' .
      'ADD COLUMN `status` varchar(12) NULL DEFAULT NULL,' .
      'ADD COLUMN `priority` mediumint(9) NOT NULL DEFAULT 0,' .
      'ADD COLUMN `scheduled_at` TIMESTAMP NULL,' .
      'ADD COLUMN `processed_at` TIMESTAMP NULL'
    );
  }

  private function restoreTable() {
    global $wpdb;
    $wpdb->query(
      'ALTER TABLE ' . SendingQueue::$_table . ' ' .
      'DROP COLUMN `type`,' .
      'DROP COLUMN `status`,' .
      'DROP COLUMN `priority`,' .
      'DROP COLUMN `scheduled_at`,' .
      'DROP COLUMN `processed_at`'
    );
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);

    // Restore table after testing
    $this->restoreTable();
    $this->altered = false;
  }
}
