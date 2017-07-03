<?php

use Carbon\Carbon;
use MailPoet\Cron\Workers\Bounce;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge\API;
use MailPoet\Util\Helpers;

require_once('BounceTestMockAPI.php');

class BounceTest extends MailPoetTest {
  function _before() {
    $this->emails = array(
      'soft_bounce@example.com',
      'hard_bounce@example.com',
      'good_address@example.com'
    );

    foreach($this->emails as $email) {
        Subscriber::createOrUpdate(array(
          'status' => Subscriber::STATUS_SUBSCRIBED,
          'email' => $email
        ));
    }

    $this->worker = new Bounce(microtime(true));

    $this->worker->api = new MailPoet\Cron\Workers\Bounce\MockAPI('key');
  }

  function testItDefinesConstants() {
    expect(Bounce::BATCH_SIZE)->equals(100);
  }

  function testItCanInitializeBridgeAPI() {
    $this->setMailPoetSendingMethod();
    $worker = new Bounce(microtime(true));
    $worker->init();
    expect($worker->api instanceof API)->true();
  }

  function testItRequiresMailPoetMethodToBeSetUp() {
    expect($this->worker->checkProcessingRequirements())->false();
    $this->setMailPoetSendingMethod();
    expect($this->worker->checkProcessingRequirements())->true();
  }

  function testItDeletesQueueIfThereAreNoSubscribersWhenPreparingQueue() {
    Subscriber::deleteMany();
    $queue = $this->createScheduledQueue();
    $result = $this->worker->prepareQueue($queue);
    expect(SendingQueue::findOne($queue->id))->isEmpty();
    expect($result)->false();
  }

  function testItPreparesQueue() {
    $queue = $this->createScheduledQueue();
    expect(empty($queue->subscribers['to_process']))->true();
    $this->worker->prepareQueue($queue);
    expect($queue->status)->null();
    expect(!empty($queue->subscribers['to_process']))->true();
  }

  function testItDeletesQueueIfThereAreNoSubscribersToProcessWhenProcessingQueue() {
    $queue = $this->createScheduledQueue();
    $queue->subscribers = null;
    $queue->save();
    $result = $this->worker->processQueue($queue);
    expect(SendingQueue::findOne($queue->id))->isEmpty();
    expect($result)->false();
  }

  function testItProcessesQueue() {
    $queue = $this->createRunningQueue();
    $this->worker->prepareQueue($queue);
    expect(!empty($queue->subscribers['to_process']))->true();
    $this->worker->processQueue($queue);
    expect(!empty($queue->subscribers['processed']))->true();
  }

  function testItSetsSubscriberStatusAsBounced() {
    $emails = Subscriber::select('email')->findArray();
    $emails = Helpers::arrayColumn($emails, 'email');

    $this->worker->processEmails($emails);

    $subscribers = Subscriber::findMany();

    expect($subscribers[0]->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscribers[1]->status)->equals(Subscriber::STATUS_BOUNCED);
    expect($subscribers[2]->status)->equals(Subscriber::STATUS_SUBSCRIBED);
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