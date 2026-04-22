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
    verify($result)->false();
  }

  public function testIsSubscriberProcessedUnprocessed() {
    $task = $this->createTask();
    $queue = $this->createQueue($task);
    $subscriber = $this->createSubscriber();
    $this->createTaskSubscriber($task, $subscriber, 0);
    $this->entityManager->flush();

    $result = $this->repository->isSubscriberProcessed($queue, $subscriber);
    verify($result)->false();
  }

  public function testIsSubscriberProcessedProcessed() {
    $task = $this->createTask();
    $queue = $this->createQueue($task);
    $subscriber = $this->createSubscriber();
    $this->createTaskSubscriber($task, $subscriber, 1);
    $this->entityManager->flush();

    $result = $this->repository->isSubscriberProcessed($queue, $subscriber);
    verify($result)->true();
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

    verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_COMPLETED);
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_SENT);
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

    verify($task->getStatus())->null();
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
  }

  public function testItResumesSendingOfActivableNewsletter() {
    $task = $this->createTask();
    $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
    $queue = $this->createQueue($task);
    $newsletter = $queue->getNewsletter();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATION);
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $queue->setCountTotal(1);
    $queue->setCountProcessed(2);
    $this->entityManager->flush();

    $this->repository->resume($queue);
    $this->entityManager->refresh($task);

    verify($task->getStatus())->null();
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_ACTIVE);
  }

  public function testItReturnsCountOfQueuesByNewsletter() {
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
    $queue3 = $this->createQueue($task3, $newsletter);
    $queue3->setCountToProcess(5);

    $this->entityManager->flush();

    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->assertSame(7, $this->repository->countAllToProcessByNewsletter($newsletter));
  }

  private function createTaskSubscriber(ScheduledTaskEntity $task, SubscriberEntity $subscriber, int $processed) {
    $taskSubscriber = new ScheduledTaskSubscriberEntity(
      $task,
      $subscriber,
      $processed
    );
    $this->entityManager->persist($taskSubscriber);
  }

  public function testItNullsRenderedBodyForOldCompletedQueues() {
    $task = $this->createTask();
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $task->setType('sending');
    $task->setProcessedAt(new \DateTime('-40 days'));
    $queue = $this->createQueue($task);
    $queue->setNewsletterRenderedBody(['html' => '<p>hello</p>', 'text' => 'hello']);
    $queue->setNewsletterRenderedSubject('Subject A');
    $this->entityManager->flush();

    $updated = $this->repository->nullRenderedBodyForOldCompletedQueues(30, 100);

    verify($updated)->equals(1);
    $this->entityManager->refresh($queue);
    verify($queue->getNewsletterRenderedBody())->null();
  }

  public function testItSkipsQueuesWithinRetentionWindow() {
    $task = $this->createTask();
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $task->setType('sending');
    $task->setProcessedAt(new \DateTime('-10 days'));
    $queue = $this->createQueue($task);
    $queue->setNewsletterRenderedBody(['html' => '<p>recent</p>', 'text' => 'recent']);
    $this->entityManager->flush();

    $updated = $this->repository->nullRenderedBodyForOldCompletedQueues(30, 100);

    verify($updated)->equals(0);
    $this->entityManager->refresh($queue);
    verify($queue->getNewsletterRenderedBody())->notNull();
  }

  public function testItRespectsRetentionBoundary() {
    $insideTask = $this->createTask();
    $insideTask->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $insideTask->setType('sending');
    $insideTask->setProcessedAt(new \DateTime('-29 days'));
    $insideQueue = $this->createQueue($insideTask);
    $insideQueue->setNewsletterRenderedBody(['html' => '<p>inside</p>', 'text' => 'inside']);

    $outsideTask = $this->createTask();
    $outsideTask->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $outsideTask->setType('sending');
    $outsideTask->setProcessedAt(new \DateTime('-31 days'));
    $outsideQueue = $this->createQueue($outsideTask);
    $outsideQueue->setNewsletterRenderedBody(['html' => '<p>outside</p>', 'text' => 'outside']);
    $this->entityManager->flush();

    $updated = $this->repository->nullRenderedBodyForOldCompletedQueues(30, 100);

    verify($updated)->equals(1);
    $this->entityManager->refresh($insideQueue);
    $this->entityManager->refresh($outsideQueue);
    verify($insideQueue->getNewsletterRenderedBody())->notNull();
    verify($outsideQueue->getNewsletterRenderedBody())->null();
  }

  public function testItSkipsNonCompletedTasks() {
    foreach ([ScheduledTaskEntity::STATUS_SCHEDULED, ScheduledTaskEntity::STATUS_PAUSED, ScheduledTaskEntity::STATUS_CANCELLED, null] as $status) {
      $task = $this->createTask();
      $task->setStatus($status);
      $task->setType('sending');
      $task->setProcessedAt(new \DateTime('-40 days'));
      $queue = $this->createQueue($task);
      $queue->setNewsletterRenderedBody(['html' => '<p>x</p>', 'text' => 'x']);
    }
    $this->entityManager->flush();

    $updated = $this->repository->nullRenderedBodyForOldCompletedQueues(30, 100);

    verify($updated)->equals(0);
  }

  public function testItReturnsBatchCount() {
    for ($i = 0; $i < 3; $i++) {
      $task = $this->createTask();
      $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
      $task->setType('sending');
      $task->setProcessedAt(new \DateTime('-40 days'));
      $queue = $this->createQueue($task);
      $queue->setNewsletterRenderedBody(['html' => '<p>body</p>', 'text' => 'body']);
    }
    $this->entityManager->flush();

    $updated = $this->repository->nullRenderedBodyForOldCompletedQueues(30, 100);

    verify($updated)->equals(3);
  }

  public function testItRespectsBatchSize() {
    for ($i = 0; $i < 5; $i++) {
      $task = $this->createTask();
      $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
      $task->setType('sending');
      $task->setProcessedAt(new \DateTime('-40 days'));
      $queue = $this->createQueue($task);
      $queue->setNewsletterRenderedBody(['html' => '<p>body</p>', 'text' => 'body']);
    }
    $this->entityManager->flush();

    $updated = $this->repository->nullRenderedBodyForOldCompletedQueues(30, 3);

    verify($updated)->equals(3);
  }

  public function testItSkipsAlreadyNulledRows() {
    $task = $this->createTask();
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $task->setType('sending');
    $task->setProcessedAt(new \DateTime('-40 days'));
    $this->createQueue($task);
    // body already null — nothing to clean
    $this->entityManager->flush();

    $updated = $this->repository->nullRenderedBodyForOldCompletedQueues(30, 100);

    verify($updated)->equals(0);
  }

  public function testItPreservesNewsletterRenderedSubject() {
    $task = $this->createTask();
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $task->setType('sending');
    $task->setProcessedAt(new \DateTime('-40 days'));
    $queue = $this->createQueue($task);
    $queue->setNewsletterRenderedBody(['html' => '<p>hello</p>', 'text' => 'hello']);
    $queue->setNewsletterRenderedSubject('Keep this subject');
    $this->entityManager->flush();

    $this->repository->nullRenderedBodyForOldCompletedQueues(30, 100);
    $this->entityManager->refresh($queue);

    verify($queue->getNewsletterRenderedBody())->null();
    verify($queue->getNewsletterRenderedSubject())->equals('Keep this subject');
  }

  public function testItSkipsSoftDeletedQueues() {
    $task = $this->createTask();
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $task->setType('sending');
    $task->setProcessedAt(new \DateTime('-40 days'));
    $queue = $this->createQueue($task);
    $queue->setNewsletterRenderedBody(['html' => '<p>deleted</p>', 'text' => 'deleted']);
    $queue->setDeletedAt(new \DateTime());
    $this->entityManager->flush();

    $updated = $this->repository->nullRenderedBodyForOldCompletedQueues(30, 100);

    verify($updated)->equals(0);
    $this->entityManager->refresh($queue);
    verify($queue->getNewsletterRenderedBody())->notNull();
  }

  private function createTask(): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);
    return $task;
  }

  private function createQueue(ScheduledTaskEntity $task, ?NewsletterEntity $newsletter = null): SendingQueueEntity {
    if (!$newsletter) {
      $newsletter = new NewsletterEntity();
      $newsletter->setType('type');
      $newsletter->setSubject('Subject');
      $this->entityManager->persist($newsletter);
    }

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $queue->setCountToProcess(1);
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
