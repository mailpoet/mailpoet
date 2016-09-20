<?php

use Codeception\Util\Fixtures;
use Codeception\Util\Stub;
use MailPoet\API\Endpoints\Cron;
use MailPoet\Config\Populator;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\NewsletterPost;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Models\Subscriber;

class SendingQueueTest extends MailPoetTest {
  function _before() {
    $wp_users = get_users();
    wp_set_current_user($wp_users[0]->ID);
    $populator = new Populator();
    $populator->up();
    $this->subscriber = Subscriber::create();
    $this->subscriber->email = 'john@doe.com';
    $this->subscriber->first_name = 'John';
    $this->subscriber->last_name = 'Doe';
    $this->subscriber->save();
    $this->newsletter = Newsletter::create();
    $this->newsletter->type = Newsletter::TYPE_STANDARD;
    $this->newsletter->subject = Fixtures::get('newsletter_subject_template');
    $this->newsletter->body = Fixtures::get('newsletter_body_template');
    $this->newsletter->save();
    $this->queue = SendingQueue::create();
    $this->queue->newsletter_id = $this->newsletter->id;
    $this->queue->subscribers = serialize(
      array(
        'to_process' => array($this->subscriber->id),
        'processed' => array(),
        'failed' => array()
      )
    );
    $this->queue->count_total = 1;
    $this->queue->save();
    $this->sending_queue_worker = new SendingQueueWorker();
  }

  function testItConstructs() {
    expect($this->sending_queue_worker->mailer_task instanceof MailerTask);
    expect($this->sending_queue_worker->newsletter_task instanceof NewsletterTask);
    expect(strlen($this->sending_queue_worker->timer))->greaterOrEquals(5);

    // constructor accepts timer argument
    $timer = microtime(true) - 5;
    $sending_queue_worker = new SendingQueueWorker($timer);
    expect($sending_queue_worker->timer)->equals($timer);
  }

  function testItEnforcesExecutionLimitBeforeStart() {
    $timer = microtime(true) - CronHelper::DAEMON_EXECUTION_LIMIT;
    try {
      $sending_queue_worker = new SendingQueueWorker($timer);
      self::fail('Maximum execution time limit exception was not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  function testItEnforcesExecutionLimitDuringProcessing() {
    try {
      $sending_queue_worker = $this->sending_queue_worker;
      $sending_queue_worker->timer = microtime(true) - CronHelper::DAEMON_EXECUTION_LIMIT;
      $sending_queue_worker->process();
      self::fail('Maximum execution time limit exception was not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  function testItDeletesQueueWhenNewsletterIsNotFound() {
    // queue exists
    $queue = SendingQueue::findOne($this->queue->id);
    expect($queue)->notEquals(false);

    // delete newsletter
    Newsletter::findOne($this->newsletter->id)
      ->delete();

    // queue no longer exists
    $this->sending_queue_worker->process();
    $queue = SendingQueue::findOne($this->queue->id);
    expect($queue)->false(false);
  }

  function testItCanProcessSubscribersOneByOne() {
    $sending_queue_worker = $this->sending_queue_worker;
    $sending_queue_worker->mailer_task = Stub::make(
      new MailerTask(),
      array('send' => Stub::exactly(1, function($newsletter, $subscriber) { return true; }))
    );
    $sending_queue_worker->process();

    // newsletter status is set to sent
    $updated_newsletter = Newsletter::findOne($this->newsletter->id);
    expect($updated_newsletter->status)->equals(Newsletter::STATUS_SENT);

    // queue status is set to completed
    $updated_queue = SendingQueue::findOne($this->queue->id);
    expect($updated_queue->status)->equals(SendingQueue::STATUS_COMPLETED);

    // queue subscriber processed/failed/to process count is updated
    $updated_queue->subscribers = $updated_queue->getSubscribers();
    expect($updated_queue->subscribers)->equals(
      array(
        'to_process' => array(),
        'failed' => array(),
        'processed' => array($this->subscriber->id)
      )
    );
    expect($updated_queue->count_total)->equals(1);
    expect($updated_queue->count_processed)->equals(1);
    expect($updated_queue->count_failed)->equals(0);
    expect($updated_queue->count_to_process)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->id)
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  function testItCanProcessSubscribersInBulk() {
    $sending_queue_worker = $this->sending_queue_worker;
    $sending_queue_worker->mailer_task = Stub::make(
      new MailerTask(),
      array(
        'send' => Stub::exactly(1, function($newsletter, $subscriber) { return true; }),
        'getProcessingMethod' => Stub::exactly(1, function() { return 'bulk'; })
      )
    );
    $sending_queue_worker->process();

    // newsletter status is set to sent
    $updated_newsletter = Newsletter::findOne($this->newsletter->id);
    expect($updated_newsletter->status)->equals(Newsletter::STATUS_SENT);

    // queue status is set to completed
    $updated_queue = SendingQueue::findOne($this->queue->id);
    expect($updated_queue->status)->equals(SendingQueue::STATUS_COMPLETED);

    // queue subscriber processed/failed/to process count is updated
    $updated_queue->subscribers = $updated_queue->getSubscribers();
    expect($updated_queue->subscribers)->equals(
      array(
        'to_process' => array(),
        'failed' => array(),
        'processed' => array($this->subscriber->id)
      )
    );
    expect($updated_queue->count_total)->equals(1);
    expect($updated_queue->count_processed)->equals(1);
    expect($updated_queue->count_failed)->equals(0);
    expect($updated_queue->count_to_process)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->id)
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  function testItRemovesNonexistentSubscribersFromProcessingList() {
    $queue = $this->queue;
    $queue->subscribers = serialize(
      array(
        'to_process' => array(
          $this->subscriber->id(),
          123
        ),
        'processed' => array(),
        'failed' => array()
      )
    );
    $queue->count_total = 2;
    $queue->save();
    $sending_queue_worker = $this->sending_queue_worker;
    $sending_queue_worker->mailer_task = Stub::make(
      new MailerTask(),
      array('send' => Stub::exactly(1, function($newsletter, $subscriber) { return true; }))
    );
    $sending_queue_worker->process();

    $updated_queue = SendingQueue::findOne($queue->id);
    // queue subscriber processed/failed/to process count is updated
    $updated_queue->subscribers = $updated_queue->getSubscribers();
    expect($updated_queue->subscribers)->equals(
      array(
        'to_process' => array(),
        'failed' => array(),
        'processed' => array($this->subscriber->id)
      )
    );
    expect($updated_queue->count_total)->equals(1);
    expect($updated_queue->count_processed)->equals(1);
    expect($updated_queue->count_failed)->equals(0);
    expect($updated_queue->count_to_process)->equals(0);

    // statistics entry should be created only for 1 subscriber
    $statistics = StatisticsNewsletters::findMany();
    expect(count($statistics))->equals(1);
  }

  function testItUpdatesQueueSubscriberCountWhenNoneOfSubscribersExist() {
    $queue = $this->queue;
    $queue->subscribers = serialize(
      array(
        'to_process' => array(
          123,
          456
        ),
        'processed' => array(),
        'failed' => array()
      )
    );
    $queue->count_total = 2;
    $queue->save();
    $sending_queue_worker = $this->sending_queue_worker;
    $sending_queue_worker->mailer_task = Stub::make(
      new MailerTask(),
      array('send' => Stub::exactly(1, function($newsletter, $subscriber) { return true; }))
    );
    $sending_queue_worker->process();

    $updated_queue = SendingQueue::findOne($queue->id);
    // queue subscriber processed/failed/to process count is updated
    $updated_queue->subscribers = $updated_queue->getSubscribers();
    expect($updated_queue->subscribers)->equals(
      array(
        'to_process' => array(),
        'failed' => array(),
        'processed' => array()
      )
    );
    expect($updated_queue->count_total)->equals(0);
    expect($updated_queue->count_processed)->equals(0);
    expect($updated_queue->count_failed)->equals(0);
    expect($updated_queue->count_to_process)->equals(0);
  }

  function testItUpdatesFailedListWhenSendingFailed() {
    $sending_queue_worker = $this->sending_queue_worker;
    $sending_queue_worker->mailer_task = Stub::make(
      new MailerTask(),
      array('send' => Stub::exactly(1, function($newsletter, $subscriber) { return false; }))
    );
    $sending_queue_worker->process();

    // queue subscriber processed/failed/to process count is updated
    $updated_queue = SendingQueue::findOne($this->queue->id);
    $updated_queue->subscribers = $updated_queue->getSubscribers();
    expect($updated_queue->subscribers)->equals(
      array(
        'to_process' => array(),
        'failed' => array($this->subscriber->id),
        'processed' => array()
      )
    );
    expect($updated_queue->count_total)->equals(1);
    expect($updated_queue->count_processed)->equals(1);
    expect($updated_queue->count_failed)->equals(1);
    expect($updated_queue->count_to_process)->equals(0);

    // statistics entry should not be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->id)
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->false();
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterPost::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsNewsletters::$_table);
  }
}