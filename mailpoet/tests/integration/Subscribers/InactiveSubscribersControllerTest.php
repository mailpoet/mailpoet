<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SettingEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Tasks\Sending;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class InactiveSubscribersControllerTest extends \MailPoetTest {

  /** @var InactiveSubscribersController */
  private $controller;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var NewsletterEntity */
  private $newsletter;

  const INACTIVITY_DAYS_THRESHOLD = 5;
  const PROCESS_BATCH_SIZE = 1000000000;
  const UNOPENED_EMAILS_THRESHOLD = InactiveSubscribersController::UNOPENED_EMAILS_THRESHOLD;

  public function _before() {
    $this->controller = new InactiveSubscribersController(
      $this->diContainer->get(EntityManager::class)
    );
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->entityManager->getConnection()->executeQuery('DROP TABLE IF EXISTS inactive_task_ids');
    $this->newsletter = new NewsletterEntity();
    $this->newsletter->setSubject('Subject');
    $this->newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->persist($this->newsletter);
    $this->entityManager->flush();
    parent::_before();
  }

  public function testItDeactivatesOldSubscribersOnlyWhenUnopenedEmailsReachThreshold(): void {
    $subscriber1 = $this->createSubscriber('s1@email.com', 10);
    $this->createCompletedSendingTasksForSubscriber($subscriber1, self::UNOPENED_EMAILS_THRESHOLD, 3);

    $subscriber2 = $this->createSubscriber('s2@email.com', 10);
    $this->createCompletedSendingTasksForSubscriber($subscriber2, self::UNOPENED_EMAILS_THRESHOLD - 1, 3);

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(1);
    $this->entityManager->clear();
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $subscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber1->getStatus())->equals(SubscriberEntity::STATUS_INACTIVE);
    expect($subscriber2->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItDeactivatesLimitedAmountOfSubscribers(): void {
    $subscriber1 = $this->createSubscriber('s1@email.com', 10);
    $this->createCompletedSendingTasksForSubscriber($subscriber1, self::UNOPENED_EMAILS_THRESHOLD, 3);
    $subscriber2 = $this->createSubscriber('s2@email.com', 10);
    $this->createCompletedSendingTasksForSubscriber($subscriber2, self::UNOPENED_EMAILS_THRESHOLD, 3);
    $batchSize = 1;

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, $batchSize, $subscriber1->getId());
    $this->entityManager->clear();
    expect($result)->equals(1);
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $subscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber1->getStatus() === SubscriberEntity::STATUS_INACTIVE || $subscriber2->getStatus() === SubscriberEntity::STATUS_INACTIVE)->true();
    expect($subscriber1->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED || $subscriber2->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED)->true();

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, $batchSize, $subscriber2->getId(), self::UNOPENED_EMAILS_THRESHOLD);
    $this->entityManager->clear();
    expect($result)->equals(1);
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $subscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber1->getStatus())->equals(SubscriberEntity::STATUS_INACTIVE);
    expect($subscriber2->getStatus())->equals(SubscriberEntity::STATUS_INACTIVE);
  }

  public function testItDoesNotDeactivateNewSubscriberWithUnopenedEmail(): void {
    $subscriber = $this->createSubscriber('s1@email.com', 3);
    $this->createCompletedSendingTasksForSubscriber($subscriber, self::UNOPENED_EMAILS_THRESHOLD, 3);

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateNewlyResubscribedSubscriberWithUnopenedEmail(): void {
    $subscriber = $this->createSubscriber('s1@email.com', 10);
    $lastSubscribedAt = (new Carbon())->subDays(2);
    $subscriber->setLastSubscribedAt($lastSubscribedAt);
    $this->entityManager->flush();
    $this->createCompletedSendingTasksForSubscriber($subscriber, self::UNOPENED_EMAILS_THRESHOLD, 3);

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateSubscriberWithoutSentEmail(): void {
    $this->createCompletedSendingTask(3);
    $subscriber = $this->createSubscriber('s1@email.com', 10);
    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateSubscriberWhoRecentlyOpenedEmail(): void {
    [$task, $queue] = $this->createCompletedSendingTask(2);
    $subscriber = $this->createSubscriber('s1@email.com', 10);
    $this->addSubscriberToTask($subscriber, $task);
    $this->addEmailOpenedRecord($subscriber, $queue, 2);
    $this->createCompletedSendingTasksForSubscriber($subscriber, self::UNOPENED_EMAILS_THRESHOLD, 3);
    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateSubscriberWhoReceivedEmailRecently(): void {
    $subscriber = $this->createSubscriber('s1@email.com', 10);
    $this->createCompletedSendingTasksForSubscriber($subscriber, self::UNOPENED_EMAILS_THRESHOLD, 0);
    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $subscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotDeactivateSubscriberWithLessEmailsCountThanThreshold(): void {
    $subscriber1 = $this->createSubscriber('s1@email.com', 10, SubscriberEntity::STATUS_SUBSCRIBED, 9);
    $this->createCompletedSendingTasksForSubscriber($subscriber1, self::UNOPENED_EMAILS_THRESHOLD, 3);

    $result = $this->controller->markInactiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    expect($result)->equals(0);
    $this->entityManager->clear();

    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItActivatesSubscriberWhoRecentlyOpenedEmail(): void {
    [$task, $queue] = $this->createCompletedSendingTask(2);
    $subscriber = $this->createSubscriber('s1@email.com', 10, SubscriberEntity::STATUS_INACTIVE);
    $this->addSubscriberToTask($subscriber, $task);
    $this->addEmailOpenedRecord($subscriber, $queue, 2);
    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    $this->entityManager->clear();
    expect($result)->equals(1);
    $subscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItActivatesLimitedNumberOfSubscribers(): void {
    [$task, $queue] = $this->createCompletedSendingTask(3);
    $subscriber1 = $this->createSubscriber('s1@email.com', 10, SubscriberEntity::STATUS_INACTIVE);
    $subscriber2 = $this->createSubscriber('s2@email.com', 10, SubscriberEntity::STATUS_INACTIVE);
    $this->addSubscriberToTask($subscriber1, $task);
    $this->addSubscriberToTask($subscriber2, $task);
    $this->addEmailOpenedRecord($subscriber1, $queue, 2);
    $this->addEmailOpenedRecord($subscriber2, $queue, 2);
    $batchSize = 1;

    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, $batchSize);
    $this->entityManager->clear();
    expect($result)->equals(1);
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $subscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber1->getStatus() === SubscriberEntity::STATUS_INACTIVE || $subscriber2->getStatus() === SubscriberEntity::STATUS_INACTIVE)->true();
    expect($subscriber1->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED || $subscriber2->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED)->true();

    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, $batchSize);
    $this->entityManager->clear();
    expect($result)->equals(1);
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $subscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber1->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($subscriber2->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotActivateOldSubscribersWithUnopenedEmail(): void {
    [$task] = $this->createCompletedSendingTask(2);
    $subscriber = $this->createSubscriber('s1@email.com', 10, SubscriberEntity::STATUS_INACTIVE);
    $this->addSubscriberToTask($subscriber, $task);
    $result = $this->controller->markActiveSubscribers(self::INACTIVITY_DAYS_THRESHOLD, self::PROCESS_BATCH_SIZE);
    $this->entityManager->clear();
    expect($result)->equals(0);
    $subscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_INACTIVE);
  }

  public function testItDoesReactivateInactiveSubscribers(): void {
    [$task] = $this->createCompletedSendingTask(2);
    $subscriber = $this->createSubscriber('s1@email.com', 10, SubscriberEntity::STATUS_INACTIVE);
    $this->addSubscriberToTask($subscriber, $task);
    $this->controller->reactivateInactiveSubscribers();
    $this->entityManager->clear();
    $subscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  private function createSubscriber(
    string $email,
    int $createdDaysAgo = 0,
    string $status = SubscriberEntity::STATUS_SUBSCRIBED,
    int $emailCount = InactiveSubscribersController::LIFETIME_EMAILS_THRESHOLD
  ): SubscriberEntity {
    $createdAt = (new Carbon())->subDays($createdDaysAgo);
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setEmailCount($emailCount);
    $subscriber->setStatus($status);
    $subscriber->setCreatedAt($createdAt);
    $this->entityManager->persist($subscriber);
    // we need to set lastSubscribeAt after persist due to LastSubscribedAtListener
    $subscriber->setLastSubscribedAt($createdAt);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createCompletedSendingTasksForSubscriber(SubscriberEntity $subscriber, int $numTasks = 1, int $processedDaysAgo = 0): void {
    for ($i = 0; $i < $numTasks; $i++) {
      [$task] = $this->createCompletedSendingTask($processedDaysAgo);
      $this->addSubscriberToTask($subscriber, $task);
    }
  }

  private function createCompletedSendingTask(int $processedDaysAgo = 0): array {
    $processedAt = (new Carbon())->subDays($processedDaysAgo);
    $task = new ScheduledTaskEntity();
    $task->setType(Sending::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $task->setCreatedAt($processedAt);
    $task->setProcessedAt($processedAt);
    $this->entityManager->persist($task);
    $this->entityManager->flush();
    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $queue->setNewsletter($this->newsletter);
    $this->entityManager->persist($queue);
    $this->entityManager->flush();
    return [$task, $queue];
  }

  private function addSubscriberToTask(
    SubscriberEntity $subscriber,
    ScheduledTaskEntity $task,
    int $daysAgo = 0
  ): ScheduledTaskSubscriberEntity {
    $createdAt = (new Carbon())->subDays($daysAgo);
    $taskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber);
    $taskSubscriber->setCreatedAt($createdAt);
    $this->entityManager->persist($taskSubscriber);
    $this->entityManager->flush();
    return $taskSubscriber;
  }

  private function addEmailOpenedRecord(
    SubscriberEntity $subscriber,
    SendingQueueEntity $queue,
    int $daysAgo = 0
  ): StatisticsOpenEntity {
    $createdAt = (new Carbon())->subDays($daysAgo);
    $opened = new StatisticsOpenEntity($this->newsletter, $queue, $subscriber);
    $opened->setCreatedAt($createdAt);
    $subscriber->setLastEngagementAt($createdAt);
    $this->entityManager->persist($opened);
    $this->entityManager->flush();
    return $opened;
  }

  private function createSetting($name, $value, $createdAt) {
    $tableName = $this->entityManager->getClassMetadata(SettingEntity::class)->getTableName();
    $this->connection->executeStatement(
      "INSERT INTO $tableName (name, value, created_at) VALUES (?, ?, ?)",
      [$name, $value, $createdAt]
    );
  }

  private function removeSetting($name) {
    $tableName = $this->entityManager->getClassMetadata(SettingEntity::class)->getTableName();
    $this->connection->executeStatement("DELETE FROM $tableName WHERE name = ?", [$name]);
  }
}
