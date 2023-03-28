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

  public function testItFinishesSendingWhenResumingQueueWithEverythingSent() {
    $task = $this->createTask();
    $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
    $queue = $this->createQueue($task);
    $newsletter = $queue->getNewsletter();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_SENDING);
    $queue->setCountTotal(1);
    $queue->setCountProcessed(1);
    $this->entityManager->flush();

    $this->repository->resume($queue);
    $this->entityManager->refresh($task);

    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_COMPLETED);
    expect($newsletter->getStatus())->equals(NewsletterEntity::STATUS_SENT);
  }

  public function testItResumesSending() {
    $task = $this->createTask();
    $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
    $queue = $this->createQueue($task);
    $newsletter = $queue->getNewsletter();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_SENDING);
    $queue->setCountTotal(1);
    $queue->setCountProcessed(2);
    $this->entityManager->flush();

    $this->repository->resume($queue);
    $this->entityManager->refresh($task);

    expect($task->getStatus())->null();
    expect($newsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
  }

  public function testItReturnsCountOfQueuesByNewsletterAndTaskStatus() {
    $taskStatus = ScheduledTaskEntity::STATUS_PAUSED;

    $task1 = $this->createTask();
    $task1->setStatus($taskStatus);
    $queue1 = $this->createQueue($task1);
    $newsletter = $queue1->getNewsletter();

    $task2 = $this->createTask();
    $task2->setStatus($taskStatus);
    $this->createQueue($task2, $newsletter);

    $task3 = $this->createTask();
    $task3->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->createQueue($task3, $newsletter);

    $this->entityManager->flush();

    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    // should return the two queues with tasks of the type STATUS_PAUSED and exclude the queue with task of the type STATUS_SCHEDULED
    $this->assertSame(2, $this->repository->countAllByNewsletterAndTaskStatus($newsletter, $taskStatus));
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

  private function createQueue(ScheduledTaskEntity $task, NewsletterEntity $newsletter = null): SendingQueueEntity {
    if (!$newsletter) {
      $newsletter = new NewsletterEntity();
      $newsletter->setType('type');
      $newsletter->setSubject('Subject');
      $this->entityManager->persist($newsletter);
    }

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
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
}
