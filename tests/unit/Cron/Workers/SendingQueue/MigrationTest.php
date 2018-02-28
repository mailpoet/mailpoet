<?php
namespace MailPoet\Test\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Cron\Workers\SendingQueue\Migration;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Tasks\Sending as SendingTask;

class MigrationTest extends \MailPoetTest {
  function _before() {
    // Alter table to test migration
    if(!Migration::checkUnmigratedColumnsExist()) {
      $this->downgradeTable();
      $this->altered = true;
    }

    $this->subscriber_to_process = Subscriber::createOrUpdate(array(
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'email' => 'to_process@example.com'
    ));
    $this->subscriber_processed = Subscriber::createOrUpdate(array(
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'email' => 'processed@example.com'
    ));

    // subscribers should be migrated
    $this->queue_running = $this->createSendingQueue();
    $this->queue_paused = $this->createSendingQueue(SendingQueue::STATUS_PAUSED);
    $this->queue_completed = $this->createSendingQueue(SendingQueue::STATUS_COMPLETED);
    $this->queue_scheduled = $this->createSendingQueue(SendingQueue::STATUS_SCHEDULED);

    $this->worker = new Migration(microtime(true));
  }

  function testItDefinesConstants() {
    expect(Migration::BATCH_SIZE)->equals(20);
  }

  function testItChecksForACompletedMigrationBeforeRunning() {
    expect($this->worker->checkProcessingRequirements())->true();
    $this->createCompletedTask();
    expect($this->worker->checkProcessingRequirements())->false();
  }

  function testItPausesSendingWhenPreparingATask() {
    $task = $this->createScheduledTask();
    expect(MailerLog::isSendingPaused())->false();
    $this->worker->prepareTask($task);
    expect($task->status)->null();
    expect(MailerLog::isSendingPaused())->true();
  }

  function testItResumesSendingIfThereIsNothingToMigrate() {
    SendingQueue::deleteMany();
    $this->worker->pauseSending();
    expect(MailerLog::isSendingPaused())->true();
    $task = $this->createScheduledTask();
    $result = $this->worker->prepareTask($task);
    expect($result)->false();
    expect(MailerLog::isSendingPaused())->false();
  }

  function testItCompletesTaskIfThereIsNothingToMigrate() {
    SendingQueue::deleteMany();
    $task = $this->createScheduledTask();
    $result = $this->worker->prepareTask($task);
    expect(ScheduledTask::findOne($task->id)->status)->equals(ScheduledTask::STATUS_COMPLETED);
    expect($result)->false();
  }

  function testItMigratesSendingQueuesAndSubscribers() {
    expect($this->worker->getUnmigratedQueues()->count())->equals(4);
    expect(ScheduledTask::where('type', SendingTask::TASK_TYPE)->findMany())->count(0);
    expect(ScheduledTaskSubscriber::whereGt('task_id', 0)->count())->equals(0);

    $task = $this->createRunningTask();
    $this->worker->processTask($task);

    expect($this->worker->getUnmigratedQueues()->count())->equals(0);
    expect(ScheduledTask::where('type', SendingTask::TASK_TYPE)->findMany())->count(4);
    expect(ScheduledTaskSubscriber::whereGt('task_id', 0)->count())->equals(8); // 2 for task of each status

    $queue = SendingQueue::findOne($this->queue_running->id);
    $task = ScheduledTask::findOne($queue->task_id);
    expect($task->type)->equals(SendingTask::TASK_TYPE);

    $migrated_subscribers = ScheduledTaskSubscriber::where('task_id', $queue->task_id)
      ->orderByAsc('subscriber_id')
      ->findMany();
    expect($migrated_subscribers)->count(2);
    expect($migrated_subscribers[0]->processed)->equals(ScheduledTaskSubscriber::STATUS_UNPROCESSED);
    expect($migrated_subscribers[1]->processed)->equals(ScheduledTaskSubscriber::STATUS_PROCESSED);
  }

  function testItResumesSendingAfterMigratingSendingQueuesAndSubscribers() {
    $this->worker->pauseSending();
    expect(MailerLog::isSendingPaused())->true();
    $task = $this->createRunningTask();
    $this->worker->processTask($task);
    expect(MailerLog::isSendingPaused())->false();
  }

  private function createScheduledTask() {
    $task = ScheduledTask::create();
    $task->type = Migration::TASK_TYPE;
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $task->save();
    return $task;
  }

  private function createRunningTask() {
    $task = ScheduledTask::create();
    $task->type = Migration::TASK_TYPE;
    $task->status = null;
    $task->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $task->save();
    return $task;
  }

  private function createCompletedTask() {
    $task = ScheduledTask::create();
    $task->type = Migration::TASK_TYPE;
    $task->status = ScheduledTask::STATUS_COMPLETED;
    $task->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $task->save();
    return $task;
  }

  private function createSendingQueue($status = null) {
    $queue = SendingQueue::create();
    $queue->newsletter_id = 0;
    $queue->task_id = 0;
    $queue->subscribers = serialize(array(
      'to_process' => array($this->subscriber_to_process->id),
      'processed' => array($this->subscriber_processed->id)
    ));
    $queue->count_total = 2;
    $queue->count_processed = 1;
    $queue->count_to_process = 1;
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

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);

    // Restore table after testing
    if(!empty($this->altered)) {
      $this->restoreTable();
      $this->altered = false;
    }
  }
}
