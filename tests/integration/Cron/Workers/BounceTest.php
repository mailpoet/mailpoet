<?php

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\Bounce;
use MailPoet\Cron\Workers\Bounce\BounceTestMockAPI as MockAPI;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

require_once('BounceTestMockAPI.php');

class BounceTest extends \MailPoetTest {
  public $worker;
  public $emails;

  public function _before() {
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

    $this->worker = new Bounce(
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(Bridge::class)
    );

    $this->worker->api = new MockAPI();
  }

  public function testItDefinesConstants() {
    expect(Bounce::BATCH_SIZE)->equals(100);
  }

  public function testItCanInitializeBridgeAPI() {
    $this->setMailPoetSendingMethod();
    $worker = new Bounce($this->diContainer->get(SettingsController::class), $this->diContainer->get(Bridge::class));
    $worker->init();
    expect($worker->api instanceof API)->true();
  }

  public function testItRequiresMailPoetMethodToBeSetUp() {
    expect($this->worker->checkProcessingRequirements())->false();
    $this->setMailPoetSendingMethod();
    expect($this->worker->checkProcessingRequirements())->true();
  }

  public function testItDeletesAllSubscribersIfThereAreNoSubscribersToProcessWhenPreparingTask() {
    // 1st run - subscribers will be processed
    $task = $this->createScheduledTask();
    $this->worker->prepareTaskStrategy($task, microtime(true));
    expect(ScheduledTaskSubscriber::where('task_id', $task->id)->findMany())->notEmpty();

    // 2nd run - nothing more to process, ScheduledTaskSubscriber will be cleaned up
    Subscriber::deleteMany();
    $task = $this->createScheduledTask();
    $this->worker->prepareTaskStrategy($task, microtime(true));
    expect(ScheduledTaskSubscriber::where('task_id', $task->id)->findMany())->isEmpty();
  }

  public function testItPreparesTask() {
    $task = $this->createScheduledTask();
    expect(ScheduledTaskSubscriber::getUnprocessedCount($task->id))->isEmpty();
    $result = $this->worker->prepareTaskStrategy($task, microtime(true));
    expect($result)->true();
    expect(ScheduledTaskSubscriber::getUnprocessedCount($task->id))->notEmpty();
  }

  public function testItDeletesAllSubscribersIfThereAreNoSubscribersToProcessWhenProcessingTask() {
    // prepare subscribers
    $task = $this->createScheduledTask();
    $this->worker->prepareTaskStrategy($task, microtime(true));
    expect(ScheduledTaskSubscriber::where('task_id', $task->id)->findMany())->notEmpty();

    // process - no subscribers found, ScheduledTaskSubscriber will be cleaned up
    Subscriber::deleteMany();
    $task = $this->createScheduledTask();
    $this->worker->processTaskStrategy($task, microtime(true));
    expect(ScheduledTaskSubscriber::where('task_id', $task->id)->findMany())->isEmpty();
  }

  public function testItProcessesTask() {
    $task = $this->createRunningTask();
    $this->worker->prepareTaskStrategy($task, microtime(true));
    expect(ScheduledTaskSubscriber::getUnprocessedCount($task->id))->notEmpty();
    $this->worker->processTaskStrategy($task, microtime(true));
    expect(ScheduledTaskSubscriber::getProcessedCount($task->id))->notEmpty();
  }

  public function testItSetsSubscriberStatusAsBounced() {
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
    $task->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->save();
    return $task;
  }

  private function createRunningTask() {
    $task = ScheduledTask::create();
    $task->type = 'bounce';
    $task->status = null;
    $task->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->save();
    return $task;
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}
