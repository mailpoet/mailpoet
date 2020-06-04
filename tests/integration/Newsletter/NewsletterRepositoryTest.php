<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use Codeception\Util\Fixtures;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Tasks\Sending as SendingTask;

class NewsletterRepositoryTest extends \MailPoetTest {
  /** @var NewslettersRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->repository = $this->diContainer->get(NewslettersRepository::class);
  }

  public function testItBulkTrashNewslettersAndChildren() {
    $newsletter1 = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $this->createQueueWithTask($newsletter1);
    $newsletter2 = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    $newsletter2Child1 = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_SCHEDULED, $newsletter2);
    $this->createQueueWithTask($newsletter2Child1);
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

  private function createQueueWithTask(NewsletterEntity $newsletter, $status = ScheduledTaskEntity::STATUS_SCHEDULED): SendingQueueEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(SendingTask::TASK_TYPE);
    $task->setStatus($status);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);

    $newsletter->getQueues()->add($queue);
    $this->entityManager->flush();
    return $queue;
  }

  private function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
  }
}
