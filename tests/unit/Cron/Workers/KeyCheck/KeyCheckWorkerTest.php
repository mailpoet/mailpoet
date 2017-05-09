<?php

use Carbon\Carbon;
use Codeception\Util\Stub;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

require_once('KeyCheckWorkerMockImplementation.php');
use MailPoet\Cron\Workers\KeyCheck\MockKeyCheckWorker;

class KeyCheckWorkerTest extends MailPoetTest {
  function _before() {
    $this->worker = new MockKeyCheckWorker();
  }

  function testItCanInitializeBridgeAPI() {
    $this->worker->init();
    expect($this->worker->bridge instanceof Bridge)->true();
  }

  function testItReturnsTrueOnSuccessfulKeyCheck() {
    $queue = $this->createRunningQueue();
    $result = $this->worker->processQueueStrategy($queue);
    expect($result)->true();
  }

  function testItReschedulesCheckOnException() {
    $worker = Stub::make(
      $this->worker,
      array(
        'checkKey' => function () { throw new \Exception; },
        'reschedule' => Stub::once()
      ),
      $this
    );
    $queue = $this->createRunningQueue();
    $result = $worker->processQueueStrategy($queue);
    expect($result)->false();
  }

  function testItReschedulesCheckOnError() {
    $worker = Stub::make(
      $this->worker,
      array(
        'checkKey' => array('code' => Bridge::CHECK_ERROR_UNAVAILABLE),
        'reschedule' => Stub::once()
      ),
      $this
    );
    $queue = $this->createRunningQueue();
    $result = $worker->processQueueStrategy($queue);
    expect($result)->false();
  }

  private function createRunningQueue() {
    $queue = SendingQueue::create();
    $queue->type = MockKeyCheckWorker::TASK_TYPE;
    $queue->status = null;
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->newsletter_id = 0;
    $queue->save();
    return $queue;
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}