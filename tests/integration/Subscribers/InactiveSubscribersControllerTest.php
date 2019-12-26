<?php

namespace MailPoet\Subscribers;

use MailPoet\Config\MP2Migrator;
use MailPoet\Entities\SettingEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Tasks\Sending;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class InactiveSubscribersControllerTest extends \MailPoetTest {

  /** @var InactiveSubscribersController */
  private $controller;

  /** @var Newsletter */
  private $newsletter;

  const INACTIVITY_DAYS_THRESHOLD = 5;
  const PROCESS_BATCH_SIZE = 100;

  public function _before() {
    $this->controller = new InactiveSubscribersController($this->di_container->get(SettingsRepository::class));
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsOpens::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('DROP TABLE IF EXISTS inactives_task_ids');
    $this->newsletter = Newsletter::createOrUpdate([
      'subject' => "Subject ",
      "type" => Newsletter::TYPE_STANDARD,
      "status" => Newsletter::STATUS_SENT,
    ]);
    $this->newsletter->save();
    parent::_before();
  }

  public function testItDeactivatesOldSubscribersWithUnopenedEmail() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completed_days_ago = 3);

    $subscriber1 = $this->createSubscriber('s1@email.com', $created_days_ago = 10);
    $this->addSubcriberToTask($subscriber1, $task);
    $subscriber2 = $this->createSubscriber('s2@email.com', $created_days_ago = 10);
    $this->addSubcriberToTask($subscriber2, $task);

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(2);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status)->equals(Subscriber::STATUS_INACTIVE);
    expect($subscriber2->status)->equals(Subscriber::STATUS_INACTIVE);
  }

  public function testItDeactivatesLimitedAmountOfSubscribers() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completed_days_ago = 3);

    $subscriber1 = $this->createSubscriber('s1@email.com', $created_days_ago = 10);
    $this->addSubcriberToTask($subscriber1, $task);
    $subscriber2 = $this->createSubscriber('s2@email.com', $created_days_ago = 10);
    $this->addSubcriberToTask($subscriber2, $task);
    $batch_size = 1;

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, $batch_size, $subscriber1->id);
    expect($result)->equals(1);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status === Subscriber::STATUS_INACTIVE || $subscriber2->status === Subscriber::STATUS_INACTIVE)->true();
    expect($subscriber1->status === Subscriber::STATUS_SUBSCRIBED || $subscriber2->status === Subscriber::STATUS_SUBSCRIBED)->true();

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, $batch_size, $subscriber2->id);
    expect($result)->equals(1);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status)->equals(Subscriber::STATUS_INACTIVE);
    expect($subscriber2->status)->equals(Subscriber::STATUS_INACTIVE);
  }

  public function testItDoesNotDeactivateNewSubscriberWithUnopenedEmail() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completed_days_ago = 3);

    $subscriber = $this->createSubscriber('s1@email.com', $completed_days_ago = 3);
    $this->addSubcriberToTask($subscriber, $task);

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateNewlyResubscribedSubscriberWithUnopenedEmail() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completed_days_ago = 3);

    $subscriber = $this->createSubscriber('s1@email.com', $created_days_ago = 10);
    $subscriber->last_subscribed_at = (new Carbon())->subDays(2)->toDateTimeString();
    $subscriber->save();
    $this->addSubcriberToTask($subscriber, $task);

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateSubscriberWithoutSentEmail() {
    $this->createCompletedSendingTaskWithOneOpen($completed_days_ago = 3);
    $subscriber = $this->createSubscriber('s1@email.com', $created_days_ago = 10);
    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateSubscriberWhoRecentlyOpenedEmail() {
    list($task, $queue) = $this->createCompletedSendingTaskWithOneOpen($completed_days_ago = 2);
    $subscriber = $this->createSubscriber('s1@email.com', $created_days_ago = 10);
    $this->addSubcriberToTask($subscriber, $task);
    $this->addEmailOpenedRecord($subscriber, $queue, $opened_days_ago = 2);
    list($task2) = $this->createCompletedSendingTaskWithOneOpen($completed_days_ago = 2);
    $this->addSubcriberToTask($subscriber, $task2);
    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateSubscriberWhoReceivedEmailRecently() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completed_days_ago = 0);
    $subscriber = $this->createSubscriber('s1@email.com', $created_days_ago = 10);
    $this->addSubcriberToTask($subscriber, $task);
    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateSubscriberWhoReceivedEmailWhichWasNeverOpened() {
    list($task) = $this->createCompletedSendingTask($completed_days_ago = 2);
    $subscriber = $this->createSubscriber('s1@email.com', $created_days_ago = 10);
    $this->addSubcriberToTask($subscriber, $task);
    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivatesSubscribersWhenMP2MigrationHappenedWithinInterval() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completed_days_ago = 3);

    $this->createSetting(MP2Migrator::MIGRATION_COMPLETE_SETTING_KEY, true, (new Carbon())->subDays(3));

    $subscriber = $this->createSubscriber('s1@email.com', $created_days_ago = 10);
    $this->addSubcriberToTask($subscriber, $task);

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    $this->removeSetting(MP2Migrator::MIGRATION_COMPLETE_SETTING_KEY);
  }

  public function testItActivatesSubscriberWhoRecentlyOpenedEmail() {
    list($task, $queue) = $this->createCompletedSendingTask($completed_days_ago = 2);
    $subscriber = $this->createSubscriber('s1@email.com', $created_days_ago = 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber, $task);
    $this->addEmailOpenedRecord($subscriber, $queue, $opened_days_ago = 2);
    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(1);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItActivatesLimitedNumberOfSubscribers() {
    list($task, $queue) = $this->createCompletedSendingTask($completed_days_ago = 3);
    $subscriber1 = $this->createSubscriber('s1@email.com', $created_days_ago = 10, Subscriber::STATUS_INACTIVE);
    $subscriber2 = $this->createSubscriber('s2@email.com', $created_days_ago = 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber1, $task);
    $this->addSubcriberToTask($subscriber2, $task);
    $this->addEmailOpenedRecord($subscriber1, $queue, $opened_days_ago = 2);
    $this->addEmailOpenedRecord($subscriber2, $queue, $opened_days_ago = 2);
    $batch_size = 1;

    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, $batch_size);
    expect($result)->equals(1);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status === Subscriber::STATUS_INACTIVE || $subscriber2->status === Subscriber::STATUS_INACTIVE)->true();
    expect($subscriber1->status === Subscriber::STATUS_SUBSCRIBED || $subscriber2->status === Subscriber::STATUS_SUBSCRIBED)->true();

    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, $batch_size);
    expect($result)->equals(1);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriber2->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotActivateOldSubscribersWithUnopenedEmail() {
    list($task) = $this->createCompletedSendingTask($completed_days_ago = 2);
    $subscriber = $this->createSubscriber('s1@email.com', $created_days_ago = 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber, $task);
    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_INACTIVE);
  }

  public function testItActivatesSubscribersWhenMP2MigrationHappenedWithinInterval() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completed_days_ago = 3);

    $this->createSetting(MP2Migrator::MIGRATION_COMPLETE_SETTING_KEY, true, (new Carbon())->subDays(3));

    $subscriber = $this->createSubscriber('s1@email.com', $created_days_ago = 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber, $task);

    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(1);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    $this->removeSetting(MP2Migrator::MIGRATION_COMPLETE_SETTING_KEY);
  }

  public function testItDoesReactivateInactiveSubscribers() {
    list($task) = $this->createCompletedSendingTask($completed_days_ago = 2);
    $subscriber = $this->createSubscriber('s1@email.com', $created_days_ago = 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber, $task);
    $this->controller->reactivateInactiveSubscribers();
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  /**
   * @param string $email
   * @param int $created_days_ago
   * @param string $status
   * @return Subscriber
   */
  private function createSubscriber($email, $created_days_ago = 0, $status = Subscriber::STATUS_SUBSCRIBED) {
    $created_at = (new Carbon())->subDays($created_days_ago)->toDateTimeString();
    $subscriber = Subscriber::createOrUpdate(['email' => $email, 'status' => $status]);
    $subscriber->created_at = $created_at;
    $subscriber->last_subscribed_at = $created_at;
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
    $subscriber0 = $this->createSubscriber('s0@email.com', $created_days_ago = 10);
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

  private function createSetting($name, $value, $created_at) {
    $table_name = $this->entity_manager->getClassMetadata(SettingEntity::class)->getTableName();
    $this->connection->executeUpdate(
      "INSERT INTO $table_name (name, value, created_at) VALUES (?, ?, ?)",
      [$name, $value, $created_at]
    );
  }

  private function removeSetting($name) {
    $table_name = $this->entity_manager->getClassMetadata(SettingEntity::class)->getTableName();
    $this->connection->executeUpdate("DELETE FROM $table_name WHERE name = ?", [$name]);
  }
}
