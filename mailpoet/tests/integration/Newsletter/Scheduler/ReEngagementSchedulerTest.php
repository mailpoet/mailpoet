<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;

class ReEngagementSchedulerTest extends \MailPoetTest {
  /** @var NewsletterOptionFieldEntity */
  private $afterTimeNumberOptionField;

  /** @var NewsletterOptionFieldEntity */
  private $afterTimeTypeField;

  /** @var ReEngagementScheduler */
  private $scheduler;

  /** @var SegmentEntity */
  private $segment;

  /** @var NewsletterEntity */
  private $sentStandardNewsletter;

  public function _before() {
    parent::_before();
    // Prepare Newsletter field options for configuring re-engagement emails
    $this->afterTimeNumberOptionField = new NewsletterOptionFieldEntity();
    $this->afterTimeNumberOptionField->setName(NewsletterOptionFieldEntity::NAME_AFTER_TIME_NUMBER);
    $this->afterTimeNumberOptionField->setNewsletterType(NewsletterEntity::TYPE_RE_ENGAGEMENT);
    $this->entityManager->persist($this->afterTimeNumberOptionField);
    $this->afterTimeTypeField = new NewsletterOptionFieldEntity();
    $this->afterTimeTypeField->setName(NewsletterOptionFieldEntity::NAME_AFTER_TIME_TYPE);
    $this->afterTimeTypeField->setNewsletterType(NewsletterEntity::TYPE_RE_ENGAGEMENT);
    $this->entityManager->persist($this->afterTimeTypeField);

    $this->segment = (new Segment())->withName('Re-engagement test')->create();
    $this->sentStandardNewsletter = (new Newsletter())->withSentStatus()->withSendingQueue()->create();

    $this->scheduler = $this->diContainer->get(ReEngagementScheduler::class);
  }

  public function testItDoesntScheduleAnythingIfThereAreNoActiveReEngagementEmails() {
    $this->createReEngagementEmail(5, NewsletterEntity::STATUS_DRAFT); // Inactive re-engagement email
    $scheduled = $this->scheduler->scheduleAll();
    expect($scheduled)->count(0);
  }

  public function testItDoesntScheduleAnythingIfThereAreNoSubscribersToSendTo() {
    $this->createReEngagementEmail(5);
    $scheduled = $this->scheduler->scheduleAll();
    expect($scheduled)->count(0);
  }

  public function testItScheduleEmailWithCorrectSubscribers() {
    $beforeCheckInterval = Carbon::now();
    $beforeCheckInterval->subMonths(10);
    $withinCheckInterval = Carbon::now();
    $withinCheckInterval->subMonth();
    $reEngagementEmail = $this->createReEngagementEmail(5);

    // SUBSCRIBERS WHO SHOULD RECEIVE THE RE-ENGAGEMENT EMAIL

    // Subscriber who should match all conditions and should and be scheduled
    $subscriberToBeScheduled = $this->createSubscriber('ok_subscriber@example.com', $beforeCheckInterval, $this->segment);
    $this->addSentEmailToSubscriber($this->sentStandardNewsletter, $subscriberToBeScheduled, $withinCheckInterval);

    // Subscriber who received re-engagement email but in the past
    $subscriberToBeScheduledWithReEmail = $this->createSubscriber('with_reemail_in_past@example.com', $beforeCheckInterval, $this->segment);
    $this->addSentEmailToSubscriber($this->sentStandardNewsletter, $subscriberToBeScheduledWithReEmail, $withinCheckInterval);
    $this->addSentEmailToSubscriber($reEngagementEmail, $subscriberToBeScheduledWithReEmail, $beforeCheckInterval);

    // SUBSCRIBERS WHO SHOULD NOT RECEIVE THE RE-ENGAGEMENT EMAIL

    // Subscriber who received re-engagement email within the interval
    $subscriberWithReEmail = $this->createSubscriber('with_reemail_in_interval@example.com', $beforeCheckInterval, $this->segment);
    $this->addSentEmailToSubscriber($this->sentStandardNewsletter, $subscriberWithReEmail, $withinCheckInterval);
    $this->addSentEmailToSubscriber($reEngagementEmail, $subscriberWithReEmail, $withinCheckInterval);

    // Subscriber who didn't receive any newsletter within the interval
    $subscriberWithoutSentEmail = $this->createSubscriber('without_email@example.com', $beforeCheckInterval, $this->segment);

    // Subscriber who is globally subscribed but unsubscribed from the re-engagement email's list
    $subscriberUnsubscribedFromList = $this->createSubscriber('list_unsubscribed@example.com', $beforeCheckInterval, $this->segment);
    $this->entityManager->refresh($subscriberUnsubscribedFromList);
    $this->addSentEmailToSubscriber($this->sentStandardNewsletter, $subscriberUnsubscribedFromList, $withinCheckInterval);
    $subscriberSegment = $subscriberUnsubscribedFromList->getSubscriberSegments()->first();
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberSegment);
    $subscriberSegment->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);

    // Subscriber who is not globally subscribed
    $unsubscribedSubscriber = $this->createSubscriber('global_unsubscribed@example.com', $beforeCheckInterval, $this->segment);
    $this->addSentEmailToSubscriber($this->sentStandardNewsletter, $unsubscribedSubscriber, $withinCheckInterval);
    $unsubscribedSubscriber->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);

    // Subscriber who is not in the list
    $notInListSubscriber = $this->createSubscriber('without_list@example.com', $beforeCheckInterval, null);
    $this->addSentEmailToSubscriber($this->sentStandardNewsletter, $notInListSubscriber, $withinCheckInterval);

    // Subscriber who received the only email within the last day
    $subscriberWithOnlyRecentEmail = $this->createSubscriber('only_recent_email@example.com', $beforeCheckInterval, $this->segment);
    $oneHourAgo = Carbon::now();
    $oneHourAgo->subHour();
    $this->addSentEmailToSubscriber($this->sentStandardNewsletter, $subscriberWithOnlyRecentEmail, $oneHourAgo);

    // Subscriber who engaged recently
    $subscriberWithoutRecentEngagement = $this->createSubscriber('with_engagement@example.com', $withinCheckInterval, $this->segment);
    $this->addSentEmailToSubscriber($this->sentStandardNewsletter, $subscriberWithoutRecentEngagement, $withinCheckInterval);

    $this->entityManager->flush();

    $task = $this->scheduler->scheduleAll()[0];
    $this->entityManager->refresh($task);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
    expect($task->getType())->equals(Sending::TASK_TYPE);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    expect($scheduledAt->getTimestamp())->equals(Carbon::now()->getTimestamp(), 1);
    expect($task->getSubscribers()->count())->equals(2);

    $sendingQueue = $this->entityManager->getRepository(SendingQueueEntity::class)->findOneBy(['task' => $task]);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    expect($sendingQueue->getCountToProcess())->equals(2);
    expect($sendingQueue->getCountTotal())->equals(2);
    expect($sendingQueue->getCountProcessed())->equals(0);
  }

  public function testItSchedulesOneSubscriberInTwoSegmentsOnlyOnce() {
    $beforeCheckInterval = Carbon::now();
    $beforeCheckInterval->subMonths(10);
    $withinCheckInterval = Carbon::now();
    $withinCheckInterval->subMonth();
    $this->createReEngagementEmail(5);

    // Subscriber who should match all conditions and should and be scheduled
    $subscriberToBeScheduled = $this->createSubscriber('ok_subscriber@example.com', $beforeCheckInterval, $this->segment);
    $this->entityManager->refresh($subscriberToBeScheduled);
    $this->addSentEmailToSubscriber($this->sentStandardNewsletter, $subscriberToBeScheduled, $withinCheckInterval);

    $secondSegment = (new Segment())->withName('Re-engagement test 2')->create();
    $subscriberSegment = new SubscriberSegmentEntity($secondSegment, $subscriberToBeScheduled, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriberSegment);
    $subscriberToBeScheduled->getSubscriberSegments()->add($subscriberSegment);
    $this->entityManager->flush();

    $task = $this->scheduler->scheduleAll()[0];
    $this->entityManager->refresh($task);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getSubscribers()->count())->equals(1);

    $sendingQueue = $this->entityManager->getRepository(SendingQueueEntity::class)->findOneBy(['task' => $task]);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    expect($sendingQueue->getCountToProcess())->equals(1);
  }

  private function createReEngagementEmail(int $monthsAfter, string $status = NewsletterEntity::STATUS_ACTIVE) {
    $email = (new Newsletter())
      ->withSubject("Re-engagement $monthsAfter months")
      ->withSendingQueue()
      ->create();
    $email->setType(NewsletterEntity::TYPE_RE_ENGAGEMENT);
    $email->setStatus($status);
    $afterTimeType = new NewsletterOptionEntity($email, $this->afterTimeTypeField);
    $afterTimeType->setValue('months');
    $this->entityManager->persist($afterTimeType);
    $email->getOptions()->add($afterTimeType);
    $afterTimeNumber = new NewsletterOptionEntity($email, $this->afterTimeNumberOptionField);
    $afterTimeNumber->setValue((string)$monthsAfter);
    $this->entityManager->persist($afterTimeNumber);
    $email->getOptions()->add($afterTimeNumber);
    $this->entityManager->persist($email);
    $emailSegment = new NewsletterSegmentEntity($email, $this->segment);
    $this->entityManager->persist($emailSegment);
    $email->getNewsletterSegments()->add($emailSegment);
    $this->entityManager->flush();
    return $email;
  }

  private function addSentEmailToSubscriber(NewsletterEntity $email, SubscriberEntity $subscriber, \DateTimeInterface $sentAt) {
    $queue = $email->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $sentStats = new StatisticsNewsletterEntity($email, $queue, $subscriber);
    $sentStats->setSentAt($sentAt);
    $this->entityManager->persist($sentStats);
    $this->entityManager->flush();
  }

  private function createSubscriber($email, $lastEngagement, SegmentEntity $segment = null) {
    $factory = new Subscriber();
    if ($segment) {
      $factory = $factory->withSegments([$segment]);
    }
    $subscriber = $factory
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withEmail($email)
      ->create();
    $subscriber->setCreatedAt($lastEngagement);
    $subscriber->setLastEngagementAt($lastEngagement);
    $subscriber->setLastSubscribedAt($lastEngagement);
    $this->entityManager->flush();
    return $subscriber;
  }
}
