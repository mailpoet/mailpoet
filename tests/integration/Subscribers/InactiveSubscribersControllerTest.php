<?php

namespace MailPoet\Subscribers;

use Carbon\Carbon;
use MailPoet\Config\MP2Migrator;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Tasks\Sending;

class InactiveSubscribersControllerTest extends \MailPoetTest {

  /** @var InactiveSubscribersController */
  private $controller;

  /** @var Newsletter */
  private $newsletter;

  function _before() {
    $this->controller = new InactiveSubscribersController();
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . StatisticsOpens::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    $this->newsletter = Newsletter::createOrUpdate([
      'subject' => "Subject ",
      "type" => Newsletter::TYPE_STANDARD,
      "status" => Newsletter::STATUS_SENT,
    ]);
    $this->newsletter->save();
    parent::_before();
  }

  function testItDeactivatesOldSubscribersWithUnopenedEmail() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen(3);

    $subscriber1 = $this->createSubscriber('s1@email.com', 10);
    $this->addSubcriberToTask($subscriber1, $task);
    $subscriber2 = $this->createSubscriber('s2@email.com', 10);
    $this->addSubcriberToTask($subscriber2, $task);

    $result = $this->controller->markInactiveSubscribers(5, 100);
    expect($result)->equals(2);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status)->equals(Subscriber::STATUS_INACTIVE);
    expect($subscriber2->status)->equals(Subscriber::STATUS_INACTIVE);
  }

  function testItDeactivatesLimitedAmountOfSubscribers() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen(3);

    $subscriber1 = $this->createSubscriber('s1@email.com', 10);
    $this->addSubcriberToTask($subscriber1, $task);
    $subscriber2 = $this->createSubscriber('s2@email.com', 10);
    $this->addSubcriberToTask($subscriber2, $task);

    $result = $this->controller->markInactiveSubscribers(5, 1);
    expect($result)->equals(1);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status === Subscriber::STATUS_INACTIVE || $subscriber2->status === Subscriber::STATUS_INACTIVE)->true();
    expect($subscriber1->status === Subscriber::STATUS_SUBSCRIBED || $subscriber2->status === Subscriber::STATUS_SUBSCRIBED)->true();

    $result = $this->controller->markInactiveSubscribers(5, 1);
    expect($result)->equals(1);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status)->equals(Subscriber::STATUS_INACTIVE);
    expect($subscriber2->status)->equals(Subscriber::STATUS_INACTIVE);
  }

  function testItDoesNotDeactivateNewSubscriberWithUnopenedEmail() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen(3);

    $subscriber = $this->createSubscriber('s1@email.com', 2);
    $this->addSubcriberToTask($subscriber, $task);

    $result = $this->controller->markInactiveSubscribers(5, 100);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  function testItDoesNotDeactivateNewlyConfirmedSubscriberWithUnopenedEmail() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen(3);

    $subscriber = $this->createSubscriber('s1@email.com', 10);
    $subscriber->confirmed_at = (new Carbon())->subDays(2)->toDateTimeString();
    $subscriber->save();
    $this->addSubcriberToTask($subscriber, $task);

    $result = $this->controller->markInactiveSubscribers(5, 100);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  function testItDoesNotDeactivateSubscriberWithoutSentEmail() {
    $this->createCompletedSendingTaskWithOneOpen(3);
    $subscriber = $this->createSubscriber('s1@email.com', 10);
    $result = $this->controller->markInactiveSubscribers(5, 100);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  function testItDoesNotDeactivateSubscriberWhoRecentlyOpenedEmail() {
    list($task, $queue) = $this->createCompletedSendingTaskWithOneOpen(2);
    $subscriber = $this->createSubscriber('s1@email.com', 10);
    $this->addSubcriberToTask($subscriber, $task);
    $this->addEmailOpenedRecord($subscriber, $queue, 2);
    list($task2) = $this->createCompletedSendingTaskWithOneOpen(2);
    $this->addSubcriberToTask($subscriber, $task2);
    $result = $this->controller->markInactiveSubscribers(5, 100);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  function testItDoesNotDeactivateSubscriberWhoReceivedEmailRecently() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen(0);
    $subscriber = $this->createSubscriber('s1@email.com', 10);
    $this->addSubcriberToTask($subscriber, $task);
    $result = $this->controller->markInactiveSubscribers(5, 100);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  function testItDoesNotDeactivateSubscriberWhoReceivedEmailWhichWasNeverOpened() {
    list($task) = $this->createCompletedSendingTask(2);
    $subscriber = $this->createSubscriber('s1@email.com', 10);
    $this->addSubcriberToTask($subscriber, $task);
    $result = $this->controller->markInactiveSubscribers(5, 100);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  function testItDoesNotDeactivatesSubscribersWhenMP2MigrationHappenedWithinInterval() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen(3);

    $migration_complete_setting = Setting::createOrUpdate([
      'name' => MP2Migrator::MIGRATION_COMPLETE_SETTING_KEY,
      'created_at' => (new Carbon())->subDays(3),
    ]);

    $subscriber = $this->createSubscriber('s1@email.com', 10);
    $this->addSubcriberToTask($subscriber, $task);

    $result = $this->controller->markInactiveSubscribers(5, 100);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    $migration_complete_setting->delete();
  }

  function testItActivatesSubscriberWhoRecentlyOpenedEmail() {
    list($task, $queue) = $this->createCompletedSendingTask(2);
    $subscriber = $this->createSubscriber('s1@email.com', 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber, $task);
    $this->addEmailOpenedRecord($subscriber, $queue, 2);
    $result = $this->controller->markActiveSubscribers(5, 100);
    expect($result)->equals(1);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  function testItActivatesLimitedNumberOfSubscribers() {
    list($task, $queue) = $this->createCompletedSendingTask(2);
    $subscriber1 = $this->createSubscriber('s1@email.com', 10, Subscriber::STATUS_INACTIVE);
    $subscriber2 = $this->createSubscriber('s2@email.com', 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber1, $task);
    $this->addSubcriberToTask($subscriber2, $task);
    $this->addEmailOpenedRecord($subscriber1, $queue, 2);
    $this->addEmailOpenedRecord($subscriber2, $queue, 2);

    $result = $this->controller->markActiveSubscribers(5, 1);
    expect($result)->equals(1);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status === Subscriber::STATUS_INACTIVE || $subscriber2->status === Subscriber::STATUS_INACTIVE)->true();
    expect($subscriber1->status === Subscriber::STATUS_SUBSCRIBED || $subscriber2->status === Subscriber::STATUS_SUBSCRIBED)->true();

    $result = $this->controller->markActiveSubscribers(5, 1);
    expect($result)->equals(1);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriber2->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  function testItDoesNotActivateOldSubscribersWithUnopenedEmail() {
    list($task) = $this->createCompletedSendingTask(2);
    $subscriber = $this->createSubscriber('s1@email.com', 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber, $task);
    $result = $this->controller->markActiveSubscribers(5, 100);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_INACTIVE);
  }

  function testItActivatesSubscribersWhenMP2MigrationHappenedWithinInterval() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen(3);

    $migration_complete_setting = Setting::createOrUpdate([
      'name' => MP2Migrator::MIGRATION_COMPLETE_SETTING_KEY,
      'created_at' => (new Carbon())->subDays(3),
    ]);

    $subscriber = $this->createSubscriber('s1@email.com', 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber, $task);

    $result = $this->controller->markActiveSubscribers(5, 100);
    expect($result)->equals(1);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    $migration_complete_setting->delete();
  }

  function testItDoesReactivateInactiveSubscribers() {
    list($task) = $this->createCompletedSendingTask(2);
    $subscriber = $this->createSubscriber('s1@email.com', 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber, $task);
    $this->controller->reactivateInactiveSubscribers();
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  /**
   * @param $email
   * @param int $created_days_ago
   * @param string $status
   * @return Subscriber
   */
  private function createSubscriber($email, $created_days_ago = 0, $status = Subscriber::STATUS_SUBSCRIBED) {
    $created_at = (new Carbon())->subDays($created_days_ago)->toDateTimeString();
    $subscriber = Subscriber::createOrUpdate(['email' => $email, 'status' => $status]);
    $subscriber->created_at = $created_at;
    $subscriber->save();
    return $subscriber;
  }

  /**
   * @param int $processed_days_ago
   * @return array
   */
  private function createCompletedSendingTask($processed_days_ago = 0) {
    $processed_at = (new Carbon())->subDays($processed_days_ago)->toDateTimeString();
    $task = ScheduledTask::createOrUpdate(['type' => Sending::TASK_TYPE, 'status' => ScheduledTask::STATUS_COMPLETED]);
    $task->created_at = $processed_at;
    $task->processed_at = $processed_at;
    $task->save();
    $queue = SendingQueue::createOrUpdate(['task_id' => $task->id, 'newsletter_id' => $this->newsletter->id]);
    $queue->save();
    return [$task, $queue];
  }

  /**
   * @param int $processed_days_ago
   * @return array
   */
  private function createCompletedSendingTaskWithOneOpen($processed_days_ago = 0) {
    list($task, $queue) = $this->createCompletedSendingTask($processed_days_ago);
    $subscriber0 = $this->createSubscriber('s0@email.com', 10);
    $this->addSubcriberToTask($subscriber0, $task);
    $this->addEmailOpenedRecord($subscriber0, $queue);
    return [$task, $queue];
  }

  /**
   * @param Subscriber $subscriber
   * @param ScheduledTask $task
   * @param int $days_ago
   */
  private function addSubcriberToTask(Subscriber $subscriber, ScheduledTask $task, $days_ago = 0) {
    $created_at = (new Carbon())->subDays($days_ago)->toDateTimeString();
    $task_subscriber = ScheduledTaskSubscriber::createOrUpdate(['task_id' => $task->id, 'subscriber_id' => $subscriber->id]);
    $task_subscriber->created_at = $created_at;
    $task_subscriber->save();
  }

  private function addEmailOpenedRecord(Subscriber $subscriber, SendingQueue $queue, $days_ago = 0) {
    $opened = StatisticsOpens::createOrUpdate(['subscriber_id' => $subscriber->id, 'newsletter_id' => $queue->newsletter_id, 'queue_id' => $queue->id]);
    $opened->created_at = (new Carbon())->subDays($days_ago)->toDateTimeString();
    $opened->save();
  }
}
