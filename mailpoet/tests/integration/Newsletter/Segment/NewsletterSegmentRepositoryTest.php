<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Segment;

use Codeception\Util\Fixtures;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Tasks\Sending as SendingTask;

class NewsletterSegmentRepositoryTest extends \MailPoetTest {
  /** @var NewsletterSegmentRepository */
  private $repository;

  /** @var NewsletterOptionFieldEntity */
  private $welcomeEmailSegmentOption;

  /** @var NewsletterOptionFieldEntity */
  private $automaticEmailSegmentOption;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(NewsletterSegmentRepository::class);
    $this->welcomeEmailSegmentOption = $this->createNewsletterOptionField(NewsletterEntity::TYPE_WELCOME, NewsletterOptionFieldEntity::NAME_SEGMENT);
    $this->automaticEmailSegmentOption = $this->createNewsletterOptionField(NewsletterEntity::TYPE_AUTOMATIC, NewsletterOptionFieldEntity::NAME_SEGMENT);
  }

  public function testItCanGetActiveNewslettersForSegments() {
    $draftNewsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, 'Draft');
    $scheduledNewsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, 'Scheduled', NewsletterEntity::STATUS_SCHEDULED);
    $this->createQueueWithTaskAndSegment($scheduledNewsletter);
    $sendingNewsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, 'Sending', NewsletterEntity::STATUS_SENDING);
    $this->createQueueWithTaskAndSegment($sendingNewsletter, null);
    $welcomeEmail = $this->createNewsletter(NewsletterEntity::TYPE_WELCOME, 'Welcome');
    $welcomeEmail2 = $this->createNewsletter(NewsletterEntity::TYPE_WELCOME, 'Welcome2');
    $automaticEmail = $this->createNewsletter(NewsletterEntity::TYPE_AUTOMATIC, 'Automatic');
    $postNotification = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, 'Notification');

    $unusedSegment = $this->createSegment('Unused', SegmentEntity::TYPE_DEFAULT);
    $dynamicWithScheduledNewsletter = $this->createSegment('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC);
    $this->addSegmentToNewsletter($scheduledNewsletter, $dynamicWithScheduledNewsletter);
    $segmentWithSendingEmail = $this->createSegment('Sending Segment', SegmentEntity::TYPE_DEFAULT);
    $this->addSegmentToNewsletter($sendingNewsletter, $segmentWithSendingEmail);
    $segmentWithAutomaticEmail = $this->createSegment('Automatic Segment', SegmentEntity::TYPE_DEFAULT);
    $this->addSegmentToNewsletter($automaticEmail, $segmentWithAutomaticEmail);
    $segmentWithWelcomeEmail = $this->createSegment('Welcome Segment', SegmentEntity::TYPE_DEFAULT);
    $this->addSegmentToNewsletter($welcomeEmail, $segmentWithWelcomeEmail);
    $segmentWithPostNotification = $this->createSegment('Notification Segment', SegmentEntity::TYPE_DEFAULT);
    $this->addSegmentToNewsletter($postNotification, $segmentWithPostNotification);

    $segmentWithMultipleActiveEmails = $this->createSegment('Multiple Segment', SegmentEntity::TYPE_DEFAULT);
    $this->addSegmentToNewsletter($postNotification, $segmentWithMultipleActiveEmails);
    $this->addSegmentToNewsletter($welcomeEmail2, $segmentWithMultipleActiveEmails);
    $this->addSegmentToNewsletter($sendingNewsletter, $segmentWithMultipleActiveEmails);

    $usedSegments = $this->repository->getSubjectsOfActivelyUsedEmailsForSegments([
      $unusedSegment->getId(),
      $dynamicWithScheduledNewsletter->getId(),
      $segmentWithSendingEmail->getId(),
      $segmentWithAutomaticEmail->getId(),
      $segmentWithWelcomeEmail->getId(),
      $segmentWithPostNotification->getId(),
      $segmentWithMultipleActiveEmails->getId(),
    ]);

    expect(isset($usedSegments[$unusedSegment->getId()]))->false();
    expect($usedSegments[$dynamicWithScheduledNewsletter->getId()])->equals(['Scheduled']);
    expect($usedSegments[$segmentWithSendingEmail->getId()])->equals(['Sending']);
    expect($usedSegments[$segmentWithAutomaticEmail->getId()])->equals(['Automatic']);
    expect($usedSegments[$segmentWithWelcomeEmail->getId()])->equals(['Welcome']);
    expect($usedSegments[$segmentWithPostNotification->getId()])->equals(['Notification']);
    sort($usedSegments[$segmentWithMultipleActiveEmails->getId()]);
    expect($usedSegments[$segmentWithMultipleActiveEmails->getId()])->equals(['Notification', 'Sending', 'Welcome2']);
  }

  private function createNewsletter(string $type, $subject, string $status = NewsletterEntity::STATUS_DRAFT): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType($type);
    $newsletter->setSubject($subject);
    $newsletter->setBody(Fixtures::get('newsletter_body_template'));
    $newsletter->setStatus($status);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }

  private function createSegment(string $name, string $type): SegmentEntity {
    $segment = new SegmentEntity($name, $type, 'Description');
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
  }

  private function addSegmentToNewsletter(NewsletterEntity $newsletter, SegmentEntity $segment) {
    if (in_array($newsletter->getType(), [NewsletterEntity::TYPE_AUTOMATIC, NewsletterEntity::TYPE_WELCOME])) {
      $this->createNewsletterOption(
        $newsletter,
        $newsletter->getType() === NewsletterEntity::TYPE_AUTOMATIC ? $this->automaticEmailSegmentOption : $this->welcomeEmailSegmentOption,
        (string)$segment->getId()
      );
    } else {
      $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
      $newsletter->getNewsletterSegments()->add($newsletterSegment);
      $this->entityManager->persist($newsletterSegment);
      $this->entityManager->flush();
    }
  }

  private function createQueueWithTaskAndSegment(NewsletterEntity $newsletter, $status = ScheduledTaskEntity::STATUS_SCHEDULED): SendingQueueEntity {
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

  private function createNewsletterOptionField(string $newsletterType, string $name): NewsletterOptionFieldEntity {
    $newsletterOptionField = new NewsletterOptionFieldEntity();
    $newsletterOptionField->setNewsletterType($newsletterType);
    $newsletterOptionField->setName($name);
    $this->entityManager->persist($newsletterOptionField);
    $this->entityManager->flush();
    return $newsletterOptionField;
  }

  private function createNewsletterOption(NewsletterEntity $newsletter, NewsletterOptionFieldEntity $field, $value): NewsletterOptionEntity {
    $option = new NewsletterOptionEntity($newsletter, $field);
    $option->setValue($value);
    $this->entityManager->persist($option);
    $this->entityManager->flush();
    return $option;
  }
}
