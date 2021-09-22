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
    $this->controller = new InactiveSubscribersController($this->diContainer->get(SettingsRepository::class));
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
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completedDaysAgo = 3);

    $subscriber1 = $this->createSubscriber('s1@email.com', $createdDaysAgo = 10);
    $this->addSubcriberToTask($subscriber1, $task);
    $subscriber2 = $this->createSubscriber('s2@email.com', $createdDaysAgo = 10);
    $this->addSubcriberToTask($subscriber2, $task);

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(2);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status)->equals(Subscriber::STATUS_INACTIVE);
    expect($subscriber2->status)->equals(Subscriber::STATUS_INACTIVE);
  }

  public function testItDeactivatesLimitedAmountOfSubscribers() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completedDaysAgo = 3);

    $subscriber1 = $this->createSubscriber('s1@email.com', $createdDaysAgo = 10);
    $this->addSubcriberToTask($subscriber1, $task);
    $subscriber2 = $this->createSubscriber('s2@email.com', $createdDaysAgo = 10);
    $this->addSubcriberToTask($subscriber2, $task);
    $batchSize = 1;

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, $batchSize, $subscriber1->id);
    expect($result)->equals(1);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status === Subscriber::STATUS_INACTIVE || $subscriber2->status === Subscriber::STATUS_INACTIVE)->true();
    expect($subscriber1->status === Subscriber::STATUS_SUBSCRIBED || $subscriber2->status === Subscriber::STATUS_SUBSCRIBED)->true();

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, $batchSize, $subscriber2->id);
    expect($result)->equals(1);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status)->equals(Subscriber::STATUS_INACTIVE);
    expect($subscriber2->status)->equals(Subscriber::STATUS_INACTIVE);
  }

  public function testItDoesNotDeactivateNewSubscriberWithUnopenedEmail() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completedDaysAgo = 3);

    $subscriber = $this->createSubscriber('s1@email.com', $completedDaysAgo = 3);
    $this->addSubcriberToTask($subscriber, $task);

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateNewlyResubscribedSubscriberWithUnopenedEmail() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completedDaysAgo = 3);

    $subscriber = $this->createSubscriber('s1@email.com', $createdDaysAgo = 10);
    $subscriber->lastSubscribedAt = (new Carbon())->subDays(2)->toDateTimeString();
    $subscriber->save();
    $this->addSubcriberToTask($subscriber, $task);

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateSubscriberWithoutSentEmail() {
    $this->createCompletedSendingTaskWithOneOpen($completedDaysAgo = 3);
    $subscriber = $this->createSubscriber('s1@email.com', $createdDaysAgo = 10);
    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateSubscriberWhoRecentlyOpenedEmail() {
    list($task, $queue) = $this->createCompletedSendingTaskWithOneOpen($completedDaysAgo = 2);
    $subscriber = $this->createSubscriber('s1@email.com', $createdDaysAgo = 10);
    $this->addSubcriberToTask($subscriber, $task);
    $this->addEmailOpenedRecord($subscriber, $queue, $openedDaysAgo = 2);
    list($task2) = $this->createCompletedSendingTaskWithOneOpen($completedDaysAgo = 2);
    $this->addSubcriberToTask($subscriber, $task2);
    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateSubscriberWhoReceivedEmailRecently() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completedDaysAgo = 0);
    $subscriber = $this->createSubscriber('s1@email.com', $createdDaysAgo = 10);
    $this->addSubcriberToTask($subscriber, $task);
    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivatesSubscribersWhenMP2MigrationHappenedWithinInterval() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completedDaysAgo = 3);

    $this->createSetting(MP2Migrator::MIGRATION_COMPLETE_SETTING_KEY, true, (new Carbon())->subDays(3));

    $subscriber = $this->createSubscriber('s1@email.com', $createdDaysAgo = 10);
    $this->addSubcriberToTask($subscriber, $task);

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    $this->removeSetting(MP2Migrator::MIGRATION_COMPLETE_SETTING_KEY);
  }

  public function testItActivatesSubscriberWhoRecentlyOpenedEmail() {
    list($task, $queue) = $this->createCompletedSendingTask($completedDaysAgo = 2);
    $subscriber = $this->createSubscriber('s1@email.com', $createdDaysAgo = 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber, $task);
    $this->addEmailOpenedRecord($subscriber, $queue, $openedDaysAgo = 2);
    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(1);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItActivatesLimitedNumberOfSubscribers() {
    list($task, $queue) = $this->createCompletedSendingTask($completedDaysAgo = 3);
    $subscriber1 = $this->createSubscriber('s1@email.com', $createdDaysAgo = 10, Subscriber::STATUS_INACTIVE);
    $subscriber2 = $this->createSubscriber('s2@email.com', $createdDaysAgo = 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber1, $task);
    $this->addSubcriberToTask($subscriber2, $task);
    $this->addEmailOpenedRecord($subscriber1, $queue, $openedDaysAgo = 2);
    $this->addEmailOpenedRecord($subscriber2, $queue, $openedDaysAgo = 2);
    $batchSize = 1;

    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, $batchSize);
    expect($result)->equals(1);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status === Subscriber::STATUS_INACTIVE || $subscriber2->status === Subscriber::STATUS_INACTIVE)->true();
    expect($subscriber1->status === Subscriber::STATUS_SUBSCRIBED || $subscriber2->status === Subscriber::STATUS_SUBSCRIBED)->true();

    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, $batchSize);
    expect($result)->equals(1);
    $subscriber1 = Subscriber::findOne($subscriber1->id);
    $subscriber2 = Subscriber::findOne($subscriber2->id);
    expect($subscriber1->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriber2->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotActivateOldSubscribersWithUnopenedEmail() {
    list($task) = $this->createCompletedSendingTask($completedDaysAgo = 2);
    $subscriber = $this->createSubscriber('s1@email.com', $createdDaysAgo = 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber, $task);
    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_INACTIVE);
  }

  public function testItActivatesSubscribersWhenMP2MigrationHappenedWithinInterval() {
    list($task) = $this->createCompletedSendingTaskWithOneOpen($completedDaysAgo = 3);

    $this->createSetting(MP2Migrator::MIGRATION_COMPLETE_SETTING_KEY, true, (new Carbon())->subDays(3));

    $subscriber = $this->createSubscriber('s1@email.com', $createdDaysAgo = 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber, $task);

    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(1);
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    $this->removeSetting(MP2Migrator::MIGRATION_COMPLETE_SETTING_KEY);
  }

  public function testItDoesReactivateInactiveSubscribers() {
    list($task) = $this->createCompletedSendingTask($completedDaysAgo = 2);
    $subscriber = $this->createSubscriber('s1@email.com', $createdDaysAgo = 10, Subscriber::STATUS_INACTIVE);
    $this->addSubcriberToTask($subscriber, $task);
    $this->controller->reactivateInactiveSubscribers();
    $subscriber = Subscriber::findOne($subscriber->id);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  /**
   * @param string $email
   * @param int $createdDaysAgo
   * @param string $status
   * @return Subscriber
   */
  private function createSubscriber($email, $createdDaysAgo = 0, $status = Subscriber::STATUS_SUBSCRIBED) {
    $createdAt = (new Carbon())->subDays($createdDaysAgo)->toDateTimeString();
    $subscriber = Subscriber::createOrUpdate(['email' => $email, 'status' => $status]);
    $subscriber->createdAt = $createdAt;
    $subscriber->lastSubscribedAt = $createdAt;
    $subscriber->save();
    return $subscriber;
  }

  /**
   * @param int $processedDaysAgo
   * @return array
   */
  private function createCompletedSendingTask($processedDaysAgo = 0) {
    $processedAt = (new Carbon())->subDays($processedDaysAgo)->toDateTimeString();
    $task = ScheduledTask::createOrUpdate(['type' => Sending::TASK_TYPE, 'status' => ScheduledTask::STATUS_COMPLETED]);
    $task->createdAt = $processedAt;
    $task->processedAt = $processedAt;
    $task->save();
    $queue = SendingQueue::createOrUpdate(['task_id' => $task->id, 'newsletter_id' => $this->newsletter->id]);
    $queue->save();
    return [$task, $queue];
  }

  /**
   * @param int $processedDaysAgo
   * @return array
   */
  private function createCompletedSendingTaskWithOneOpen($processedDaysAgo = 0) {
    list($task, $queue) = $this->createCompletedSendingTask($processedDaysAgo);
    $subscriber0 = $this->createSubscriber('s0@email.com', $createdDaysAgo = 10);
    $this->addSubcriberToTask($subscriber0, $task);
    $this->addEmailOpenedRecord($subscriber0, $queue);
    return [$task, $queue];
  }

  /**
   * @param Subscriber $subscriber
   * @param ScheduledTask $task
   * @param int $daysAgo
   */
  private function addSubcriberToTask(Subscriber $subscriber, ScheduledTask $task, $daysAgo = 0) {
    $createdAt = (new Carbon())->subDays($daysAgo)->toDateTimeString();
    $taskSubscriber = ScheduledTaskSubscriber::createOrUpdate(['task_id' => $task->id, 'subscriber_id' => $subscriber->id]);
    $taskSubscriber->createdAt = $createdAt;
    $taskSubscriber->save();
  }

  private function addEmailOpenedRecord(Subscriber $subscriber, SendingQueue $queue, $daysAgo = 0) {
    $opened = StatisticsOpens::createOrUpdate(['subscriber_id' => $subscriber->id, 'newsletter_id' => $queue->newsletterId, 'queue_id' => $queue->id]);
    $opened->createdAt = (new Carbon())->subDays($daysAgo)->toDateTimeString();
    $opened->save();
    $subscriber->lastEngagementAt = (new Carbon())->subDays($daysAgo)->toDateTimeString();
    $subscriber->save();
  }

  private function createSetting($name, $value, $createdAt) {
    $tableName = $this->entityManager->getClassMetadata(SettingEntity::class)->getTableName();
    $this->connection->executeUpdate(
      "INSERT INTO $tableName (name, value, created_at) VALUES (?, ?, ?)",
      [$name, $value, $createdAt]
    );
  }

  private function removeSetting($name) {
    $tableName = $this->entityManager->getClassMetadata(SettingEntity::class)->getTableName();
    $this->connection->executeUpdate("DELETE FROM $tableName WHERE name = ?", [$name]);
  }
}
