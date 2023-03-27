<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Tasks\Sending;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SubscribersEmailCountsControllerTest extends \MailPoetTest {
  /** @var SubscribersEmailCountsController */
  private $controller;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var NewsletterEntity */
  private $newsletter;

  public function _before() {
    $this->controller = new SubscribersEmailCountsController(
      $this->diContainer->get(EntityManager::class)
    );
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->truncateEntityBackup(SubscriberEntity::class);
    $this->truncateEntityBackup(ScheduledTaskEntity::class);
    $this->truncateEntityBackup(ScheduledTaskSubscriberEntity::class);
    $this->truncateEntityBackup(SendingQueueEntity::class);
    $this->truncateEntityBackup(NewsletterEntity::class);
    $this->entityManager->getConnection()->executeQuery('DROP TABLE IF EXISTS processed_task_ids');
    $this->newsletter = new NewsletterEntity();
    $this->newsletter->setSubject('Subject');
    $this->newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->persist($this->newsletter);
    $this->entityManager->flush();
    parent::_before();
  }

  public function testItCalculatesTotalSubscribersEmailCounts(): void {
    $subscriber1 = $this->createSubscriber('s1@email.com', 100);
    $subscriber2 = $this->createSubscriber('s2@email.com', 10);
    $subscriber3 = $this->createSubscriber('s3@email.com', 10);

    $this->createCompletedSendingTasksForSubscriber($subscriber1, 80, 90);
    $this->createCompletedSendingTasksForSubscriber($subscriber2, 8, 3);

    [$count, $maxSubscriberId] = $this->controller->updateSubscribersEmailCounts(null, 3);
    expect($count)->equals(3);

    $this->entityManager->clear();
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $subscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $subscriber3 = $this->subscribersRepository->findOneById($subscriber3->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);

    expect($subscriber1->getEmailCount())->equals(80);
    expect($subscriber2->getEmailCount())->equals(8);
    expect($subscriber3->getEmailCount())->equals(0);
  }

  public function testItIncrementsSubscribersEmailCountsWhenDateProvided(): void {
    $subscriber1 = $this->createSubscriber('s1@email.com', 100, SubscriberEntity::STATUS_SUBSCRIBED, 80);
    $subscriber2 = $this->createSubscriber('s2@email.com', 20, SubscriberEntity::STATUS_SUBSCRIBED, 8);
    $subscriber3 = $this->createSubscriber('s3@email.com', 10);

    $this->createCompletedSendingTasksForSubscriber($subscriber1, 1, 5);
    $this->createCompletedSendingTasksForSubscriber($subscriber2, 1, 5);
    $this->createCompletedSendingTasksForSubscriber($subscriber3, 1, 5);


    $dateFromCarbon = new Carbon();
    $dateFrom = $dateFromCarbon->subDays(7)->toDateTime();

    [$count, $maxSubscriberId] = $this->controller->updateSubscribersEmailCounts($dateFrom, 3);
    expect($count)->equals(3);

    $this->entityManager->clear();
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $subscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $subscriber3 = $this->subscribersRepository->findOneById($subscriber3->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);

    expect($subscriber1->getEmailCount())->equals(81);
    expect($subscriber2->getEmailCount())->equals(9);
    expect($subscriber3->getEmailCount())->equals(1);

  }

  public function testItResetsValueToTotalIfNoDateProvided(): void {
    $subscriber1 = $this->createSubscriber('s1@email.com', 100, SubscriberEntity::STATUS_SUBSCRIBED, 80);
    $subscriber2 = $this->createSubscriber('s2@email.com', 20, SubscriberEntity::STATUS_SUBSCRIBED, 8);
    $subscriber3 = $this->createSubscriber('s3@email.com', 10);

    $this->createCompletedSendingTasksForSubscriber($subscriber1, 80, 90);
    $this->createCompletedSendingTasksForSubscriber($subscriber2, 8, 3);
    $this->createCompletedSendingTasksForSubscriber($subscriber3, 1, 4);

    // Count
    $this->controller->updateSubscribersEmailCounts(null, 3);
    // Recount
    $this->controller->updateSubscribersEmailCounts(null, 3);

    $this->entityManager->clear();
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $subscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $subscriber3 = $this->subscribersRepository->findOneById($subscriber3->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);

    expect($subscriber1->getEmailCount())->equals(80);
    expect($subscriber2->getEmailCount())->equals(8);
    expect($subscriber3->getEmailCount())->equals(1);
  }

  public function testItUpdatesOnlySubscribersInBatch() {
    $subscriber1 = $this->createSubscriber('s1@email.com', 100, SubscriberEntity::STATUS_SUBSCRIBED, 80);
    $subscriber2 = $this->createSubscriber('s2@email.com', 20, SubscriberEntity::STATUS_SUBSCRIBED, 8);
    $subscriber3 = $this->createSubscriber('s3@email.com', 10);

    $this->createCompletedSendingTasksForSubscriber($subscriber1, 80, 90);
    $this->createCompletedSendingTasksForSubscriber($subscriber2, 8, 3);
    $this->createCompletedSendingTasksForSubscriber($subscriber3, 1, 4);

    // First batch of 1
    [$count, $maxSubscriberId] = $this->controller->updateSubscribersEmailCounts(null, 1);
    expect($count)->equals(1);
    // Second batch of 1
    $this->controller->updateSubscribersEmailCounts(null, 1, $maxSubscriberId + 1);

    $this->entityManager->clear();
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $subscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $subscriber3 = $this->subscribersRepository->findOneById($subscriber3->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);

    expect($subscriber1->getEmailCount())->equals(80);
    expect($subscriber2->getEmailCount())->equals(8);
    // Subscriber not in batch should not be updated
    expect($subscriber3->getEmailCount())->equals(0);
  }

  public function testItDoesNotCountIfThereAreNoSubscribersOrTasksToUpdate() {
    // Subscribers empty table
    [$count, $maxSubscriberId] = $this->controller->updateSubscribersEmailCounts(null, 1);
    expect($count)->equals(0);

    $subscriber1 = $this->createSubscriber('s1@email.com', 100);
    $subscriber2 = $this->createSubscriber('s2@email.com', 20);
    $subscriber3 = $this->createSubscriber('s3@email.com', 10);

    // Tasks empty table
    $dateFromCarbon = new Carbon();
    $dateFrom = $dateFromCarbon->subDays(7)->toDateTime();
    [$count, $maxSubscriberId] = $this->controller->updateSubscribersEmailCounts($dateFrom, 1);
    expect($count)->equals(0);

    $this->createCompletedSendingTasksForSubscriber($subscriber1, 80, 90);
    $this->createCompletedSendingTasksForSubscriber($subscriber2, 8, 3);
    $this->createCompletedSendingTasksForSubscriber($subscriber3, 1, 4);

    // No subscribers to update from startId
    [$count, $maxSubscriberId] = $this->controller->updateSubscribersEmailCounts(null, 1, 4);
    expect($count)->equals(0);

    $this->entityManager->clear();
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $subscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $subscriber3 = $this->subscribersRepository->findOneById($subscriber3->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);

    expect($subscriber1->getEmailCount())->equals(0);
    expect($subscriber2->getEmailCount())->equals(0);
    expect($subscriber3->getEmailCount())->equals(0);
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

  private function createSubscriber(
    string $email,
    int $createdDaysAgo = 0,
    string $status = SubscriberEntity::STATUS_SUBSCRIBED,
    int $emailCounts = 0
  ): SubscriberEntity {
    $createdAt = (new Carbon())->subDays($createdDaysAgo);
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setStatus($status);
    $subscriber->setCreatedAt($createdAt);
    $subscriber->setEmailCount($emailCounts);
    $this->entityManager->persist($subscriber);
    // we need to set lastSubscribeAt after persist due to LastSubscribedAtListener
    $subscriber->setLastSubscribedAt($createdAt);
    $this->entityManager->flush();
    return $subscriber;
  }
}
