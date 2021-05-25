<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;

class SendingQueuesRepositoryTest extends \MailPoetTest {
  /** @var SendingQueuesRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->repository = $this->diContainer->get(SendingQueuesRepository::class);
  }

  public function testIsSubscriberProcessedTaskMissing() {
    $task = $this->createTask();
    $queue = $this->createQueue($task);
    $subscriber = $this->createSubscriber();
    $this->entityManager->flush();

    $this->entityManager->remove($task);
    $this->entityManager->flush();
    $this->entityManager->refresh($queue);

    $result = $this->repository->isSubscriberProcessed($queue, $subscriber);
    expect($result)->false();
  }

  public function testIsSubscriberProcessedUnprocessed() {
    $task = $this->createTask();
    $queue = $this->createQueue($task);
    $subscriber = $this->createSubscriber();
    $this->createTaskSubscriber($task, $subscriber, 0);
    $this->entityManager->flush();

    $result = $this->repository->isSubscriberProcessed($queue, $subscriber);
    expect($result)->false();
  }

  public function testIsSubscriberProcessedProcessed() {
    $task = $this->createTask();
    $queue = $this->createQueue($task);
    $subscriber = $this->createSubscriber();
    $this->createTaskSubscriber($task, $subscriber, 1);
    $this->entityManager->flush();

    $result = $this->repository->isSubscriberProcessed($queue, $subscriber);
    expect($result)->true();
  }

  private function createTaskSubscriber(ScheduledTaskEntity $task, SubscriberEntity $subscriber, int $processed) {
    $taskSubscriber = new ScheduledTaskSubscriberEntity(
      $task,
      $subscriber,
      $processed
    );
    $this->entityManager->persist($taskSubscriber);
  }

  private function createTask(): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);
    return $task;
  }

  private function createQueue(ScheduledTaskEntity $task): SendingQueueEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType('type');
    $newsletter->setSubject('Subject');
    $this->newsletter = $newsletter;
    $this->entityManager->persist($newsletter);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($this->newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);

    return $queue;
  }

  private function createSubscriber(): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setEmail('a@example.com');
    $this->entityManager->persist($subscriber);
    return $subscriber;
  }

  public function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
  }
}
