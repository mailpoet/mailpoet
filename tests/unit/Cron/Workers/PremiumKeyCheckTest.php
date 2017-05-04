<?php

use Carbon\Carbon;
use Codeception\Util\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\PremiumKeyCheck;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

class PremiumKeyCheckTest extends MailPoetTest {
  function _before() {
    $this->worker = new PremiumKeyCheck(microtime(true));
  }

  function testItConstructs() {
    expect($this->worker->timer)->notEmpty();
  }

  function testItThrowsExceptionWhenExecutionLimitIsReached() {
    try {
      $sskeycheck = new PremiumKeyCheck(microtime(true) - CronHelper::DAEMON_EXECUTION_LIMIT);
      self::fail('Maximum execution time limit exception was not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  function testItSchedulesPremiumKeyCheck() {
    expect(SendingQueue::where('type', PremiumKeyCheck::TASK_TYPE)->findMany())->isEmpty();
    PremiumKeyCheck::schedule();
    expect(SendingQueue::where('type', PremiumKeyCheck::TASK_TYPE)->findMany())->notEmpty();
  }

  function testItDoesNotSchedulePremiumKeyCheckTwice() {
    expect(count(SendingQueue::where('type', PremiumKeyCheck::TASK_TYPE)->findMany()))->equals(0);
    PremiumKeyCheck::schedule();
    expect(count(SendingQueue::where('type', PremiumKeyCheck::TASK_TYPE)->findMany()))->equals(1);
    PremiumKeyCheck::schedule();
    expect(count(SendingQueue::where('type', PremiumKeyCheck::TASK_TYPE)->findMany()))->equals(1);
  }

  function testItCanGetScheduledQueues() {
    expect(PremiumKeyCheck::getScheduledQueues())->isEmpty();
    $this->createScheduledQueue();
    expect(PremiumKeyCheck::getScheduledQueues())->notEmpty();
  }

  function testItCanGetRunningQueues() {
    expect(PremiumKeyCheck::getRunningQueues())->isEmpty();
    $this->createRunningQueue();
    expect(PremiumKeyCheck::getRunningQueues())->notEmpty();
  }

  function testItCanGetAllDueQueues() {
    expect(PremiumKeyCheck::getAllDueQueues())->isEmpty();

    // scheduled for now
    $this->createScheduledQueue();

    // running
    $this->createRunningQueue();

    // scheduled in the future (should not be retrieved)
    $queue = $this->createScheduledQueue();
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'))->addDays(7);
    $queue->save();

    // completed (should not be retrieved)
    $queue = $this->createRunningQueue();
    $queue->status = SendingQueue::STATUS_COMPLETED;
    $queue->save();

    expect(count(PremiumKeyCheck::getAllDueQueues()))->equals(2);
  }

  function testItCanGetFutureQueues() {
    expect(PremiumKeyCheck::getFutureQueues())->isEmpty();
    $queue = $this->createScheduledQueue();
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'))->addDays(7);
    $queue->save();
    expect(count(PremiumKeyCheck::getFutureQueues()))->notEmpty();
  }

  function testItFailsToProcessWithoutMailPoetMethodSetUp() {
    expect($this->worker->process())->false();
  }

  function testItFailsToProcessWithoutQueues() {
    $this->fillPremiumKey();
    expect($this->worker->process())->false();
  }

  function testItProcesses() {
    $this->worker->bridge = Stub::make(
      new Bridge,
      array('checkPremiumKey' => array('code' => Bridge::PREMIUM_KEY_VALID)),
      $this
    );
    $this->fillPremiumKey();
    $this->createScheduledQueue();
    $this->createRunningQueue();
    expect($this->worker->process())->true();
  }

  function testItPreparesPremiumKeyCheckQueue() {
    $queue = $this->createScheduledQueue();
    $this->worker->prepareQueue($queue);
    expect($queue->status)->null();
  }

  function testItProcessesPremiumKeyCheckQueue() {
    $this->worker->bridge = Stub::make(
      new Bridge,
      array('checkPremiumKey' => array('code' => Bridge::PREMIUM_KEY_VALID)),
      $this
    );
    $this->fillPremiumKey();
    $queue = $this->createRunningQueue();
    $this->worker->prepareQueue($queue);
    $this->worker->processQueue($queue);
    expect($queue->status)->equals(SendingQueue::STATUS_COMPLETED);
  }

  function testItReschedulesCheckOnException() {
    $this->worker->bridge = Stub::make(
      new Bridge,
      array('checkPremiumKey' => function () { throw new \Exception(); }),
      $this
    );
    $this->fillPremiumKey();
    $queue = $this->createRunningQueue();
    $scheduled_at = $queue->scheduled_at;
    $this->worker->prepareQueue($queue);
    $this->worker->processQueue($queue);
    expect($scheduled_at < $queue->scheduled_at)->true();
  }

  function testItReschedulesCheckOnError() {
    $this->worker->bridge = Stub::make(
      new Bridge,
      array('checkPremiumKey' => array('code' => Bridge::CHECK_ERROR_UNAVAILABLE)),
      $this
    );
    $this->fillPremiumKey();
    $queue = $this->createRunningQueue();
    $scheduled_at = $queue->scheduled_at;
    $this->worker->prepareQueue($queue);
    $this->worker->processQueue($queue);
    expect($scheduled_at < $queue->scheduled_at)->true();
  }

  function testItCalculatesNextRunDateWithinNextWeekBoundaries() {
    $current_date = Carbon::createFromTimestamp(current_time('timestamp'));
    $next_run_date = PremiumKeyCheck::getNextRunDate();
    $difference = $next_run_date->diffInDays($current_date);
    // Subtract days left in the current week
    $difference -= (Carbon::DAYS_PER_WEEK - $current_date->format('N'));
    expect($difference)->lessOrEquals(7);
    expect($difference)->greaterOrEquals(0);
  }

  private function fillPremiumKey() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_SETTING_NAME,
      '123457890abcdef'
    );
  }

  private function createScheduledQueue() {
    $queue = SendingQueue::create();
    $queue->type = PremiumKeyCheck::TASK_TYPE;
    $queue->status = SendingQueue::STATUS_SCHEDULED;
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->newsletter_id = 0;
    $queue->save();
    return $queue;
  }

  private function createRunningQueue() {
    $queue = SendingQueue::create();
    $queue->type = PremiumKeyCheck::TASK_TYPE;
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