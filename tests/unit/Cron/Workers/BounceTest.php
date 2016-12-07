<?php

use Carbon\Carbon;
use Codeception\Util\Stub;
use MailPoet\API\Endpoints\Cron;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\Bounce;
use MailPoet\Cron\Workers\Bounce\API;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Util\Helpers;

class BounceTest extends MailPoetTest {
  function _before() {
    $this->emails = array(
      'soft_bounce@example.com',
      'hard_bounce@example.com',
      'good_address@example.com'
    );

    foreach ($this->emails as $email) {
        Subscriber::createOrUpdate(array(
          'status' => Subscriber::STATUS_SUBSCRIBED,
          'email' => $email
        ));
    }

    $this->bounce = new Bounce(microtime(true));

    $api = Stub::make(new API('key'), array(
      'check' => function (array $emails) {
        return array_map(
          function ($email) {
            return array(
              'address' => $email,
              'bounce' => preg_match('/(hard|soft)/', $email, $m) ? $m[1] : null,
            );
          },
          $emails
        );
      }
    ), $this);

    $this->bounce->api = $api;
  }

  function testItConstructs() {
    expect($this->bounce->timer)->notEmpty();
  }

  function testItDefinesConstants() {
    expect(Bounce::BATCH_SIZE)->equals(100);
  }

  function testItChecksIfCurrentSendingMethodIsMailpoet() {
    expect(Bounce::checkBounceSyncAvailable())->false();
    $this->setMailPoetSendingMethod();
    expect(Bounce::checkBounceSyncAvailable())->true();
  }

  function testItThrowsExceptionWhenExecutionLimitIsReached() {
    try {
      $bounce = new Bounce(microtime(true) - CronHelper::DAEMON_EXECUTION_LIMIT);
      self::fail('Maximum execution time limit exception was not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  function testItSchedulesBounceSync() {
    expect(SendingQueue::where('type', 'bounce')->findMany())->isEmpty();
    Bounce::scheduleBounceSync();
    expect(SendingQueue::where('type', 'bounce')->findMany())->notEmpty();
  }

  function testItDoesNotScheduleBounceSyncTwice() {
    expect(count(SendingQueue::where('type', 'bounce')->findMany()))->equals(0);
    Bounce::scheduleBounceSync();
    expect(count(SendingQueue::where('type', 'bounce')->findMany()))->equals(1);
    Bounce::scheduleBounceSync();
    expect(count(SendingQueue::where('type', 'bounce')->findMany()))->equals(1);
  }

  function testItCanGetScheduledQueues() {
    expect(Bounce::getScheduledQueues())->isEmpty();
    $this->createScheduledQueue();
    expect(Bounce::getScheduledQueues())->notEmpty();
  }

  function testItCanGetRunningQueues() {
    expect(Bounce::getRunningQueues())->isEmpty();
    $this->createRunningQueue();
    expect(Bounce::getRunningQueues())->notEmpty();
  }

  function testItCanGetAllDueQueues() {
    expect(Bounce::getAllDueQueues())->isEmpty();

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

    expect(count(Bounce::getAllDueQueues()))->equals(2);
  }

  function testItCanGetFutureQueues() {
    expect(Bounce::getFutureQueues())->isEmpty();
    $queue = $this->createScheduledQueue();
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'))->addDays(7);
    $queue->save();
    expect(count(Bounce::getFutureQueues()))->notEmpty();
  }

  function testItFailsToProcessWithoutMailPoetMethodSetUp() {
    expect($this->bounce->process())->false();
  }

  function testItFailsToProcessWithoutQueues() {
    $this->setMailPoetSendingMethod();
    expect($this->bounce->process())->false();
  }

  function testItProcesses() {
    $this->setMailPoetSendingMethod();
    $this->createScheduledQueue();
    $this->createRunningQueue();
    expect($this->bounce->process())->true();
  }

  function testItPreparesBounceQueue() {
    $queue = $this->createScheduledQueue();
    expect(empty($queue->subscribers['to_process']))->true();
    $this->bounce->prepareBounceQueue($queue);
    expect($queue->status)->null();
    expect(!empty($queue->subscribers['to_process']))->true();
  }

  function testItProcessesBounceQueue() {
    $queue = $this->createRunningQueue();
    $this->bounce->prepareBounceQueue($queue);
    expect(!empty($queue->subscribers['to_process']))->true();
    $this->bounce->processBounceQueue($queue);
    expect(!empty($queue->subscribers['processed']))->true();
  }

  function testItSetsSubscriberStatusAsBounced() {
    $emails = Subscriber::select('email')->findArray();
    $emails = Helpers::arrayColumn($emails, 'email');

    $this->bounce->processEmails($emails);

    $subscribers = Subscriber::findMany();

    expect($subscribers[0]->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscribers[1]->status)->equals(Subscriber::STATUS_BOUNCED);
    expect($subscribers[2]->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  function testItCalculatesNextRunDateWithinNextWeekBoundaries() {
    $current_date = Carbon::createFromTimestamp(current_time('timestamp'));
    $next_run_date = Bounce::getNextRunDate();
    $difference = $next_run_date->diffInDays($current_date);
    // Subtract days left in the current week
    $difference -= (Carbon::DAYS_PER_WEEK - $current_date->format('N'));
    expect($difference)->lessOrEquals(7);
    expect($difference)->greaterOrEquals(0);
  }

  private function setMailPoetSendingMethod() {
    Setting::setValue(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      array(
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'some_key',
      )
    );
  }

  private function createScheduledQueue() {
    $queue = SendingQueue::create();
    $queue->type = 'bounce';
    $queue->status = SendingQueue::STATUS_SCHEDULED;
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->newsletter_id = 0;
    $queue->save();
    return $queue;
  }

  private function createRunningQueue() {
    $queue = SendingQueue::create();
    $queue->type = 'bounce';
    $queue->status = null;
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->newsletter_id = 0;
    $queue->save();
    return $queue;
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}