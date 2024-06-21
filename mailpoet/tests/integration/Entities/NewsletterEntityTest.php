<?php declare(strict_types = 1);

namespace MailPoet\Entities;

use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Test\DataFactories\NewsletterOptionField;

class NewsletterEntityTest extends \MailPoetTest {
  /** @var NewslettersRepository */
  private $newsletterRepository;

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var NewsletterOptionFieldsRepository */
  private $newsletterOptionFieldsRepository;

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  public function _before() {
    $this->newsletterRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->newsletterOptionFieldsRepository = $this->diContainer->get(NewsletterOptionFieldsRepository::class);
    $this->newsletterOptionsRepository = $this->diContainer->get(NewsletterOptionsRepository::class);
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
  }

  public function testItRemovesOrphanedSegmentRelations() {
    $newsletter = $this->createNewsletter();
    $segment = $this->segmentRepository->createOrUpdate('Segment', 'Segment description');
    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
    $this->entityManager->persist($newsletterSegment);
    $this->entityManager->flush();

    $this->entityManager->refresh($newsletter);
    verify($newsletter->getNewsletterSegments()->count())->same(1);

    $newsletter->getNewsletterSegments()->removeElement($newsletterSegment);
    $this->entityManager->flush();
    verify($newsletter->getNewsletterSegments()->count())->same(0);

    $newsletterSegments = $this->diContainer->get(NewsletterSegmentRepository::class)->findBy(['newsletter' => $newsletter]);
    verify($newsletterSegments)->arrayCount(0);
  }

  public function testItRemovesOrphanedOptionRelations() {
    $newsletter = $this->createNewsletter();
    $optionField = (new NewsletterOptionField())->findOrCreate(NewsletterOptionFieldEntity::NAME_GROUP);
    $newsletterOption = new NewsletterOptionEntity($newsletter, $optionField);
    $this->entityManager->persist($newsletterOption);
    $this->entityManager->flush();

    $this->entityManager->refresh($newsletter);
    verify($newsletter->getOptions()->count())->same(1);

    $newsletter->getOptions()->removeElement($newsletterOption);
    $this->entityManager->flush();
    verify($newsletter->getOptions()->count())->same(0);

    $newsletterSegments = $this->newsletterOptionsRepository->findBy(['newsletter' => $newsletter]);
    verify($newsletterSegments)->arrayCount(0);
  }

  public function testGetOptionReturnsCorrectData(): void {
    $optionValue = 'Some Value';
    $newsletter = $this->createNewsletter();
    $optionField = (new NewsletterOptionField())->findOrCreate(NewsletterOptionFieldEntity::NAME_EVENT);
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

    verify($newsletterOptionField)->notNull();
    verify($newsletterOption->getValue())->equals($optionValue);
    verify($newsletter->getOption(NewsletterOptionFieldEntity::NAME_SEGMENT))->null();
  }

  public function testItPausesTaskWhenPausingNewsletter() {
    // prepare
    $newsletter = $this->createNewsletter();
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);
    $task = new ScheduledTaskEntity();
    $task->setType(SendingQueue::TASK_TYPE);
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
    verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);
  }

  public function testItActivatesTaskWhenActivatingNewsletter() {
    // prepare
    $newsletter = $this->createNewsletter();
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $task = new ScheduledTaskEntity();
    $task->setType(SendingQueue::TASK_TYPE);
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
    verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
  }

  public function testItDoesNotActivateTaskIfInTooMuchInPast() {
    // prepare
    $newsletter = $this->createNewsletter();
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $task = new ScheduledTaskEntity();
    $task->setType(SendingQueue::TASK_TYPE);
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
    verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);
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

  public function testItCanRetrieveFilterSegmentIdOption(): void {
    $optionField = (new NewsletterOptionField())->findOrCreate(NewsletterOptionFieldEntity::NAME_FILTER_SEGMENT_ID);
    $newsletter = $this->createNewsletter();
    verify($newsletter->getFilterSegmentId())->null();

    $newsletterOption = new NewsletterOptionEntity($newsletter, $optionField);
    $newsletterOption->setValue('2');

    $this->entityManager->persist($newsletterOption);
    $this->entityManager->flush();

    $this->entityManager->refresh($newsletter);
    verify($newsletter->getFilterSegmentId())->equals(2);
  }

  public function testItInheritsFilterSegmentIdFromParent(): void {
    $optionField = (new NewsletterOptionField())->findOrCreate(NewsletterOptionFieldEntity::NAME_FILTER_SEGMENT_ID, NewsletterEntity::TYPE_NOTIFICATION);
    $notificationNewsletter = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION);
    verify($notificationNewsletter->getFilterSegmentId())->null();

    $newsletterOption = new NewsletterOptionEntity($notificationNewsletter, $optionField);
    $newsletterOption->setValue('2');

    $this->entityManager->persist($newsletterOption);
    $this->entityManager->flush();

    $this->entityManager->refresh($notificationNewsletter);
    verify($notificationNewsletter->getFilterSegmentId())->equals(2);

    $notificationHistoryNewsletter = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    verify($notificationHistoryNewsletter->getFilterSegmentId())->null();

    $notificationHistoryNewsletter->setParent($notificationNewsletter);
    $this->entityManager->persist($notificationHistoryNewsletter);
    $this->entityManager->flush();
    $this->entityManager->refresh($notificationHistoryNewsletter);
    verify($notificationHistoryNewsletter->getFilterSegmentId())->equals(2);
  }

  public function testIsTransactional(): void {
    $newsletter = $this->createNewsletter();
    $this->assertFalse($newsletter->isTransactional());

    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL);
    $this->assertTrue($newsletter->isTransactional());
    $newsletter->setType(NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL);
    $this->assertTrue($newsletter->isTransactional());
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATIC);
    $this->assertFalse($newsletter->isTransactional());

    /** @var NewsletterOptionFieldEntity $field */
    $field = $this->newsletterOptionFieldsRepository->findOneBy([
      'name' => NewsletterOptionFieldEntity::NAME_EVENT,
      'newsletterType' => $newsletter->getType(),
    ]);

    $option = new NewsletterOptionEntity($newsletter, $field);
    $option->setValue(AbandonedCart::SLUG);
    $this->newsletterOptionsRepository->persist($option);
    $newsletter->getOptions()->add($option);
    $this->assertTrue($newsletter->isTransactional());
  }

  private function createNewsletter(string $type = NewsletterEntity::TYPE_STANDARD): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType($type);
    $newsletter->setSubject('Subject');
    $this->entityManager->persist($newsletter);
    return $newsletter;
  }
}
