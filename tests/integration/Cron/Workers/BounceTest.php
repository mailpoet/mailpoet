<?php

namespace MailPoet\Test\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Cron\Workers\Bounce;
use MailPoet\Cron\Workers\Bounce\BounceTestMockAPI as MockAPI;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoetVendor\Idiorm\ORM;

require_once('BounceTestMockAPI.php');

class BounceTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    $this->emails = [
      'soft_bounce@example.com',
      'hard_bounce@example.com',
      'good_address@example.com',
    ];

    foreach ($this->emails as $email) {
        Subscriber::createOrUpdate([
          'status' => Subscriber::STATUS_SUBSCRIBED,
          'email' => $email,
        ]);
    }

    $this->worker = new Bounce($this->di_container->get(SettingsController::class), microtime(true));

    $this->worker->api = new MockAPI('key');
  }

  function testItDefinesConstants() {
    expect(Bounce::BATCH_SIZE)->equals(100);
  }

  function testItCanInitializeBridgeAPI() {
    $this->setMailPoetSendingMethod();
    $worker = new Bounce($this->di_container->get(SettingsController::class), microtime(true));
    $worker->init();
    expect($worker->api instanceof API)->true();
  }

  function testItRequiresMailPoetMethodToBeSetUp() {
    expect($this->worker->checkProcessingRequirements())->false();
    $this->setMailPoetSendingMethod();
    expect($this->worker->checkProcessingRequirements())->true();
  }

  function testItDeletesAllSubscribersIfThereAreNoSubscribersToProcessWhenPreparingTask() {
    // 1st run - subscribers will be processed
    $task = $this->createScheduledTask();
    $this->worker->prepareTask($task, microtime(true));
    expect(ScheduledTaskSubscriber::where('task_id', $task->id)->findMany())->notEmpty();

    // 2nd run - nothing more to process, ScheduledTaskSubscriber will be cleaned up
    Subscriber::deleteMany();
    $task = $this->createScheduledTask();
    $this->worker->prepareTask($task, microtime(true));
    expect(ScheduledTaskSubscriber::where('task_id', $task->id)->findMany())->isEmpty();
  }

  function testItPreparesTask() {
    $task = $this->createScheduledTask();
    expect(ScheduledTaskSubscriber::getUnprocessedCount($task->id))->isEmpty();
    $this->worker->prepareTask($task, microtime(true));
    expect($task->status)->null();
    expect(ScheduledTaskSubscriber::getUnprocessedCount($task->id))->notEmpty();
  }

  function testItDeletesAllSubscribersIfThereAreNoSubscribersToProcessWhenProcessingTask() {
    // prepare subscribers
    $task = $this->createScheduledTask();
    $this->worker->prepareTask($task, microtime(true));
    expect(ScheduledTaskSubscriber::where('task_id', $task->id)->findMany())->notEmpty();

    // process - no subscribers found, ScheduledTaskSubscriber will be cleaned up
    Subscriber::deleteMany();
    $task = $this->createScheduledTask();
    $this->worker->processTask($task, microtime(true));
    expect(ScheduledTaskSubscriber::where('task_id', $task->id)->findMany())->isEmpty();
  }

  function testItProcessesTask() {
    $task = $this->createRunningTask();
    $this->worker->prepareTask($task, microtime(true));
    expect(ScheduledTaskSubscriber::getUnprocessedCount($task->id))->notEmpty();
    $this->worker->processTask($task, microtime(true));
    expect(ScheduledTaskSubscriber::getProcessedCount($task->id))->notEmpty();
  }

  function testItSetsSubscriberStatusAsBounced() {
    $emails = Subscriber::select('email')->findArray();
    $emails = array_column($emails, 'email');

    $this->worker->processEmails($emails);

    $subscribers = Subscriber::findMany();

    expect($subscribers[0]->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscribers[1]->status)->equals(Subscriber::STATUS_BOUNCED);
    expect($subscribers[2]->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  private function setMailPoetSendingMethod() {
    $settings = SettingsController::getInstance();
    $settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'some_key',
      ]
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
    $this->di_container->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}
