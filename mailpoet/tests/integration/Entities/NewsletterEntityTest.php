<?php declare(strict_types = 1);

namespace MailPoet\Entities;

use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Tasks\Sending as SendingTask;

class NewsletterEntityTest extends \MailPoetTest {
  /** @var NewslettersRepository */
  private $newsletterRepository;

  /** @var SegmentsRepository */
  private $segmentRepository;

  public function _before() {
    $this->newsletterRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
  }

  public function testItRemovesOrphanedSegmentRelations() {
    $newsletter = $this->createNewsletter();
    $segment = $this->segmentRepository->createOrUpdate('Segment', 'Segment description');
    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
    $this->entityManager->persist($newsletterSegment);
    $this->entityManager->flush();

    $this->entityManager->refresh($newsletter);
    expect($newsletter->getNewsletterSegments()->count())->same(1);

    $newsletter->getNewsletterSegments()->removeElement($newsletterSegment);
    $this->entityManager->flush();
    expect($newsletter->getNewsletterSegments()->count())->same(0);

    $newsletterSegments = $this->diContainer->get(NewsletterSegmentRepository::class)->findBy(['newsletter' => $newsletter]);
    expect($newsletterSegments)->count(0);
  }

  public function testItRemovesOrphanedOptionRelations() {
    $newsletter = $this->createNewsletter();
    $optionField = $this->createOptionField(NewsletterOptionFieldEntity::NAME_GROUP);
    $newsletterOption = new NewsletterOptionEntity($newsletter, $optionField);
    $this->entityManager->persist($newsletterOption);
    $this->entityManager->flush();

    $this->entityManager->refresh($newsletter);
    expect($newsletter->getOptions()->count())->same(1);

    $newsletter->getOptions()->removeElement($newsletterOption);
    $this->entityManager->flush();
    expect($newsletter->getOptions()->count())->same(0);

    $newsletterSegments = $this->diContainer->get(NewsletterOptionsRepository::class)->findBy(['newsletter' => $newsletter]);
    expect($newsletterSegments)->count(0);
  }

  public function testGetOptionReturnsCorrectData(): void {
    $optionValue = 'Some Value';
    $newsletter = $this->createNewsletter();
    $optionField = $this->createOptionField(NewsletterOptionFieldEntity::NAME_EVENT);
    $newsletterOption = new NewsletterOptionEntity($newsletter, $optionField);
    $newsletterOption->setValue($optionValue);

    $this->entityManager->persist($newsletterOption);
    $this->entityManager->flush();
    $this->entityManager->clear();
    $newsletterId = $newsletter->getId();

    $newsletter = $this->newsletterRepository->findOneById($newsletterId);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $newsletterOptionField = $newsletter->getOption($optionField->getName());
    $this->assertInstanceOf(NewsletterOptionEntity::class, $newsletterOption);

    expect($newsletterOptionField)->notNull();
    expect($newsletterOption->getValue())->equals($optionValue);
    expect($newsletter->getOption(NewsletterOptionFieldEntity::NAME_SEGMENT))->null();
  }

  public function testItPausesTaskWhenPausingNewsletter() {
    // prepare
    $newsletter = $this->createNewsletter();
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);
    $task = new ScheduledTaskEntity();
    $task->setType(SendingTask::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setCountToProcess(10);
    $queue->setTask($task);
    $this->entityManager->persist($queue);

    $newsletter->getQueues()->add($queue);
    $this->entityManager->flush();

    // act
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);

    // verify
    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);
  }

  public function testItActivatesTaskWhenActivatingNewsletter() {
    // prepare
    $newsletter = $this->createNewsletter();
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $task = new ScheduledTaskEntity();
    $task->setType(SendingTask::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setCountToProcess(10);
    $queue->setTask($task);
    $this->entityManager->persist($queue);

    $newsletter->getQueues()->add($queue);
    $this->entityManager->flush();

    // act
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);

    // verify
    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
  }

  public function testItDoesNotActivateTaskIfInTooMuchInPast() {
    // prepare
    $newsletter = $this->createNewsletter();
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $task = new ScheduledTaskEntity();
    $task->setType(SendingTask::TASK_TYPE);
    $task->setScheduledAt(new \DateTimeImmutable('2012-01-02 12:23:34'));
    $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setCountToProcess(10);
    $queue->setTask($task);
    $this->entityManager->persist($queue);

    $newsletter->getQueues()->add($queue);
    $this->entityManager->flush();

    // act
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);

    // verify
    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);
  }

  public function testItGetProcessedAtReturnsNullIfEmailHasNotBeingQueuedYet() {
    $newsletter = $this->createNewsletter();
    $this->assertNull($newsletter->getProcessedAt());
  }

  public function testItGetProcessedReturnsValue() {
    $processedAt = new \DateTimeImmutable('2012-01-02 12:32:34');
    $newsletter = $this->createNewsletter();
    $task = new ScheduledTaskEntity();
    $task->setProcessedAt($processedAt);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);

    $newsletter->getQueues()->add($queue);
    $this->entityManager->flush();

    $this->assertSame($processedAt, $newsletter->getProcessedAt());
  }

  private function createNewsletter(): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Subject');
    $this->entityManager->persist($newsletter);
    return $newsletter;
  }

  private function createOptionField(string $name): NewsletterOptionFieldEntity {
    $newsletterOptionField = new NewsletterOptionFieldEntity();
    $newsletterOptionField->setName($name);
    $newsletterOptionField->setNewsletterType(NewsletterEntity::TYPE_STANDARD);
    $this->entityManager->persist($newsletterOptionField);
    return $newsletterOptionField;
  }
}
