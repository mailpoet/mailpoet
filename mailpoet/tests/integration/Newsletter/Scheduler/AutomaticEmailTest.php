<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class AutomaticEmailTest extends \MailPoetTest {

  /** @var AutomaticEmailScheduler */
  private $automaticEmailScheduler;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var NewsletterOptionFactory */
  private $newsletterOptionFactory;

  /** @var NewsletterFactory */
  private $newsletterFactory;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  public function _before() {
    parent::_before();
    $this->automaticEmailScheduler = $this->diContainer->get(AutomaticEmailScheduler::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);

    $this->newsletterFactory = new NewsletterFactory();
    $this->newsletter = $this->newsletterFactory->withActiveStatus()->withAutomaticType()->create();
    $this->newsletterOptionFactory = new NewsletterOptionFactory();
    $this->newsletterOptionFactory->createMultipleOptions(
      $this->newsletter,
      [
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
  }

  public function testItCreatesScheduledAutomaticEmailSendingTaskForUser() {
    $newsletter = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $subscriber = (new SubscriberFactory())->create();

    $this->automaticEmailScheduler->createAutomaticEmailSendingTask($newsletter, $subscriber->getId());
    // new scheduled task should be created
    $task = SendingTask::getByNewsletterId($newsletter->getId());
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    expect($task->id)->greaterOrEquals(1);
    expect($task->priority)->equals(SendingQueueEntity::PRIORITY_MEDIUM);
    expect($task->status)->equals(SendingQueueEntity::STATUS_SCHEDULED);
    expect(Carbon::parse($task->scheduledAt)->format('Y-m-d H:i'))
      ->equals($currentTime->addHours(2)->format('Y-m-d H:i'));
    // task should have 1 associated user
    $subscribers = $task->subscribers()->findMany();
    expect($subscribers)->count(1);
    expect($subscribers[0]->id)->equals($subscriber->getId());
  }

  public function testItAddsMetaToSendingQueueWhenCreatingAutomaticEmailSendingTask() {
    $newsletter = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $subscriber = (new SubscriberFactory())->create();
    $meta = ['some' => 'value'];

    $this->automaticEmailScheduler->createAutomaticEmailSendingTask($newsletter, $subscriber->getId(), $meta);
    // new queue record should be created with meta data
    $queue = $this->sendingQueuesRepository->findOneBy(['newsletter' => $newsletter]);
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $this->assertEquals($meta, $queue->getMeta());
  }

  public function testItCreatesAutomaticEmailSendingTaskForSegment() {
    $newsletter = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);

    $this->automaticEmailScheduler->createAutomaticEmailSendingTask($newsletter, $subscriber = null, $meta = null);
    // new scheduled task should be created
    $task = SendingTask::getByNewsletterId($newsletter->getId());
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    expect($task->id)->greaterOrEquals(1);
    expect($task->priority)->equals(SendingQueueEntity::PRIORITY_MEDIUM);
    expect($task->status)->equals(SendingQueueEntity::STATUS_SCHEDULED);
    expect(Carbon::parse($task->scheduledAt)->format('Y-m-d H:i'))
      ->equals($currentTime->addHours(2)->format('Y-m-d H:i'));
    // task should not have any subscribers
    $subscribers = $task->subscribers()->findMany();
    expect($subscribers)->count(0);
  }

  public function testItDoesNotScheduleAutomaticEmailWhenGroupDoesNotMatch() {
    $this->newsletterOptionFactory->createMultipleOptions(
      $this->newsletter,
      [
        'group' => 'some_group',
        'event' => 'some_event',
      ]
    );

    // email should not be scheduled when group is not matched
    $this->automaticEmailScheduler->scheduleAutomaticEmail('group_does_not_exist', 'some_event');
    $this->assertCount(0, $this->sendingQueuesRepository->findAll());
  }

  public function testItDoesNotScheduleAutomaticEmailWhenEventDoesNotMatch() {
    $this->newsletterOptionFactory->createMultipleOptions(
      $this->newsletter,
      [
        'group' => 'some_group',
        'event' => 'some_event',
      ]
    );

    // email should not be scheduled when event is not matched
    $this->automaticEmailScheduler->scheduleAutomaticEmail('some_group', 'event_does_not_exist');
    $this->assertCount(0, $this->sendingQueuesRepository->findAll());
  }

  public function testItSchedulesAutomaticEmailWhenConditionMatches() {
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $this->newsletterOptionFactory->createMultipleOptions(
      $this->newsletter,
      [
        'group' => 'some_group',
        'event' => 'some_event',
      ]
    );

    $newsletter2 = $this->newsletterFactory->withAutomaticType()->withActiveStatus()->create();
    $this->newsletterOptionFactory->createMultipleOptions(
      $newsletter2,
      [
        'group' => 'some_group',
        'event' => 'some_event',
        'sendTo' => 'segment',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $condition = function(NewsletterEntity $email) {
      return $email->getOptionValue(NewsletterOptionFieldEntity::NAME_SEND_TO) === 'segment';
    };

    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->expects($this->any())
      ->method('currentTime')
      ->willReturn($currentTime->getTimestamp());
    $automaticEmailScheduler = new AutomaticEmailScheduler(new Scheduler($wpMock, $this->diContainer->get(NewslettersRepository::class)));
    // email should only be scheduled if it matches condition ("send to segment")
    $automaticEmailScheduler->scheduleAutomaticEmail('some_group', 'some_event', $condition);
    $result = $this->sendingQueuesRepository->findAll();
    $sendingQueue = reset($result);
    expect($result)->count(1);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $newsletter = $sendingQueue->getNewsletter();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    expect($newsletter->getId())->equals($newsletter2->getId());
    // scheduled task should be created
    $task = $sendingQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getId())->greaterOrEquals(1);
    expect($task->getPriority())->equals(SendingQueueEntity::PRIORITY_MEDIUM);
    expect($task->getStatus())->equals(SendingQueueEntity::STATUS_SCHEDULED);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    expect($scheduledAt->format('Y-m-d H:i'))
      ->equals($currentTime->addHours(2)->format('Y-m-d H:i'));
  }

  public function _after() {
    Carbon::setTestNow();
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(NewsletterPostEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
  }
}
