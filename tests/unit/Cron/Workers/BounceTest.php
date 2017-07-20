<?php

use Carbon\Carbon;
use MailPoet\Cron\Workers\Bounce;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
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

  function testItDeletesTaskIfThereAreNoSubscribersWhenPreparingTask() {
    Subscriber::deleteMany();
    $task = $this->createScheduledTask();
    $result = $this->worker->prepareTask($task);
    expect(ScheduledTask::findOne($task->id))->isEmpty();
    expect($result)->false();
  }

  function testItPreparesTask() {
    $task = $this->createScheduledTask();
    expect(ScheduledTaskSubscriber::getUnprocessedCount($task->id))->isEmpty();
    $this->worker->prepareTask($task);
    expect($task->status)->null();
    expect(ScheduledTaskSubscriber::getUnprocessedCount($task->id))->notEmpty();
  }

  function testItDeletesTaskIfThereAreNoSubscribersToProcessWhenProcessingTask() {
    $task = $this->createScheduledTask();
    $task->subscribers = null;
    $task->save();
    $result = $this->worker->processTask($task);
    expect(ScheduledTask::findOne($task->id))->isEmpty();
    expect($result)->false();
  }

  function testItProcessesTask() {
    $task = $this->createRunningTask();
    $this->worker->prepareTask($task);
    expect(ScheduledTaskSubscriber::getUnprocessedCount($task->id))->notEmpty();
    $this->worker->processTask($task);
    expect(ScheduledTaskSubscriber::getProcessedCount($task->id))->notEmpty();
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

  private function createScheduledTask() {
    $task = ScheduledTask::create();
    $task->type = 'bounce';
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $task->save();
    return $task;
  }

  private function createRunningTask() {
    $task = ScheduledTask::create();
    $task->type = 'bounce';
    $task->status = null;
    $task->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $task->save();
    return $task;
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}