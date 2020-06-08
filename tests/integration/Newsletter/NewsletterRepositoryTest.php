<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use Codeception\Util\Fixtures;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Tasks\Sending as SendingTask;

class NewsletterRepositoryTest extends \MailPoetTest {
  /** @var NewslettersRepository */
  private $repository;

  /** @var ScheduledTaskSubscribersRepository */
  private $taskSubscribersRepository;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->repository = $this->diContainer->get(NewslettersRepository::class);
    $this->taskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
  }

  public function testItBulkTrashNewslettersAndChildren() {
    $newsletter1 = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $this->createQueueWithTaskAndSegmentAndSubscribers($newsletter1);
    $newsletter2 = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    $newsletter2Child1 = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_SCHEDULED, $newsletter2);
    $this->createQueueWithTaskAndSegmentAndSubscribers($newsletter2Child1);
    $this->repository->bulkTrash([$newsletter1->getId(), $newsletter2->getId()]);
    $this->entityManager->refresh($newsletter1);
    $this->entityManager->refresh($newsletter2);

    // Should trash the newsletters
    expect($newsletter1->getDeletedAt())->notNull();
    expect($newsletter2->getDeletedAt())->notNull();

    // Should trash sending queue and task
    $newsletter1Queue = $newsletter1->getLatestQueue();
    assert($newsletter1Queue instanceof SendingQueueEntity);
    $this->entityManager->refresh($newsletter1Queue);
    expect($newsletter1Queue->getDeletedAt())->notNull();
    $scheduledTask = $newsletter1Queue->getTask();
    assert($scheduledTask instanceof ScheduledTaskEntity);
    $this->entityManager->refresh($scheduledTask);
    expect($scheduledTask->getDeletedAt())->notNull();

    // Should trash children + task + queue
    $this->entityManager->refresh($newsletter2Child1);
    expect($newsletter2Child1->getDeletedAt())->notNull();
    $childrenQueue = $newsletter2Child1->getLatestQueue();
    assert($childrenQueue instanceof SendingQueueEntity);
    $this->entityManager->refresh($childrenQueue);
    expect($childrenQueue->getDeletedAt())->notNull();
    $scheduledTask = $childrenQueue->getTask();
    assert($scheduledTask instanceof ScheduledTaskEntity);
    $this->entityManager->refresh($scheduledTask);
    expect($scheduledTask->getDeletedAt())->notNull();
  }

  public function testItBulkRestoresNewslettersAndChildren() {
    $newsletter1 = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENDING);
    $this->createQueueWithTaskAndSegmentAndSubscribers($newsletter1, null); // Null for scheduled task being processed
    $newsletter2 = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    $newsletter2Child1 = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_SCHEDULED, $newsletter2);
    $this->createQueueWithTaskAndSegmentAndSubscribers($newsletter2Child1);
    // Trash
    $this->repository->bulkTrash([$newsletter1->getId(), $newsletter2->getId()]);
    // Restore
    $this->repository->bulkRestore([$newsletter1->getId(), $newsletter2->getId()]);
    $this->entityManager->refresh($newsletter1);
    $this->entityManager->refresh($newsletter2);

    // Should trash the newsletters
    expect($newsletter1->getDeletedAt())->null();
    expect($newsletter2->getDeletedAt())->null();
    expect($newsletter1->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
    expect($newsletter2->getStatus())->equals(NewsletterEntity::STATUS_ACTIVE);


    // Should restore sending queue and task
    $newsletter1Queue = $newsletter1->getLatestQueue();
    assert($newsletter1Queue instanceof SendingQueueEntity);
    $this->entityManager->refresh($newsletter1Queue);
    expect($newsletter1Queue->getDeletedAt())->null();
    $scheduledTask = $newsletter1Queue->getTask();
    assert($scheduledTask instanceof ScheduledTaskEntity);
    $this->entityManager->refresh($scheduledTask);
    expect($scheduledTask->getDeletedAt())->null();
    // Pause sending tasks which were in progress
    expect($scheduledTask->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);

    // Should restore children + task + queue
    $this->entityManager->refresh($newsletter2Child1);
    expect($newsletter2Child1->getDeletedAt())->null();
    $childrenQueue = $newsletter2Child1->getLatestQueue();
    assert($childrenQueue instanceof SendingQueueEntity);
    $this->entityManager->refresh($childrenQueue);
    expect($childrenQueue->getDeletedAt())->null();
    $scheduledTask = $childrenQueue->getTask();
    assert($scheduledTask instanceof ScheduledTaskEntity);
    $this->entityManager->refresh($scheduledTask);
    expect($scheduledTask->getDeletedAt())->null();
  }

  public function testItBulkDeleteNewslettersAndChildren() {
    $newsletter1 = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENDING);
    $this->createQueueWithTaskAndSegmentAndSubscribers($newsletter1, null); // Null for scheduled task being processed
    $newsletter2 = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    $newsletter2Child1 = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_SCHEDULED, $newsletter2);
    $this->createQueueWithTaskAndSegmentAndSubscribers($newsletter2Child1);
    // Trash
    $this->repository->bulkTrash([$newsletter1->getId(), $newsletter2->getId()]);
    $newsletter1Queue = $newsletter1->getLatestQueue();
    assert($newsletter1Queue instanceof SendingQueueEntity);
    $newsletter1Segment = $newsletter1->getNewsletterSegments()->first();
    assert($newsletter1Segment instanceof NewsletterSegmentEntity);
    $childrenQueue = $newsletter2Child1->getLatestQueue();
    assert($childrenQueue instanceof SendingQueueEntity);
    $scheduledTask1 = $newsletter1Queue->getTask();
    assert($scheduledTask1 instanceof ScheduledTaskEntity);
    $scheduledTask1Subscriber = $this->taskSubscribersRepository->findOneBy(['task' => $scheduledTask1]);
    assert($scheduledTask1Subscriber instanceof ScheduledTaskSubscriberEntity);
    $childrenScheduledTask = $childrenQueue->getTask();
    assert($childrenScheduledTask instanceof ScheduledTaskEntity);
    $childSegment = $newsletter2Child1->getNewsletterSegments()->first();
    assert($childSegment instanceof NewsletterSegmentEntity);
    $childrenScheduledTaskSubscriber = $this->taskSubscribersRepository->findOneBy(['task' => $childrenScheduledTask]);
    assert($childrenScheduledTaskSubscriber instanceof ScheduledTaskSubscriberEntity);

    // Delete
    $this->repository->bulkDelete([$newsletter1->getId(), $newsletter2->getId()]);

    // Detach entities so that ORM forget them
    $this->entityManager->detach($newsletter1);
    $this->entityManager->detach($newsletter2);
    $this->entityManager->detach($newsletter2Child1);
    $this->entityManager->detach($newsletter1Queue);
    $this->entityManager->detach($childrenQueue);
    $this->entityManager->detach($scheduledTask1);
    $this->entityManager->detach($childrenScheduledTask);
    $this->entityManager->detach($newsletter1Segment);
    $this->entityManager->detach($childSegment);

    // Check they were all deleted
    // Newsletters
    expect($this->repository->findOneById($newsletter1->getId()))->null();
    expect($this->repository->findOneById($newsletter2->getId()))->null();
    expect($this->repository->findOneById($newsletter2Child1->getId()))->null();

    // Sending queues
    expect($this->entityManager->find(SendingQueueEntity::class, $newsletter1Queue->getId()))->null();
    expect($this->entityManager->find(SendingQueueEntity::class, $childrenQueue->getId()))->null();

    // Scheduled tasks subscribers
    expect($this->taskSubscribersRepository->findOneBy(['task' => $scheduledTask1]))->null();
    expect($this->taskSubscribersRepository->findOneBy(['task' => $childrenScheduledTask]))->null();

    // Scheduled tasks
    expect($this->entityManager->find(ScheduledTaskEntity::class, $scheduledTask1->getId()))->null();
    expect($this->entityManager->find(ScheduledTaskEntity::class, $childrenScheduledTask->getId()))->null();

    // Newsletter segments
    expect($this->entityManager->find(NewsletterSegmentEntity::class, $newsletter1Segment->getId()))->null();
    expect($this->entityManager->find(NewsletterSegmentEntity::class, $childSegment->getId()))->null();
  }

  public function _after() {
    $this->cleanup();
  }

  private function createNewsletter(string $type, string $status = NewsletterEntity::STATUS_DRAFT, $parent = null): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType($type);
    $newsletter->setSubject('My Standard Newsletter');
    $newsletter->setBody(Fixtures::get('newsletter_body_template'));
    $newsletter->setStatus($status);
    $newsletter->setParent($parent);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }

  private function createQueueWithTaskAndSegmentAndSubscribers(NewsletterEntity $newsletter, $status = ScheduledTaskEntity::STATUS_SCHEDULED): SendingQueueEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(SendingTask::TASK_TYPE);
    $task->setStatus($status);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $newsletter->getQueues()->add($queue);

    $segment = new SegmentEntity();
    $segment->setType(SegmentEntity::TYPE_DEFAULT);
    $segment->setName(" List for newsletter id {$newsletter->getId()}");
    $segment->setDescription('');
    $this->entityManager->persist($segment);

    $subscriber = new SubscriberEntity();
    $subscriber->setEmail("sub{$newsletter->getId()}@mailpoet.com");
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriber);
    $scheduledTaskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($scheduledTaskSubscriber);

    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
    $newsletter->getNewsletterSegments()->add($newsletterSegment);
    $this->entityManager->persist($newsletterSegment);
    $this->entityManager->flush();
    return $queue;
  }

  private function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(NewsletterSegmentEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
  }
}
