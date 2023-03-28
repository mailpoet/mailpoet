<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
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

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function _before() {
    parent::_before();
    $this->automaticEmailScheduler = $this->diContainer->get(AutomaticEmailScheduler::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);

    $this->newsletterFactory = new NewsletterFactory();
    $this->newsletter = $this->createAutomaticNewsletter();
  }

  public function testItCreatesScheduledAutomaticEmailSendingTaskForUser() {
    $newsletter = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $subscriber = (new SubscriberFactory())->create();

    $this->automaticEmailScheduler->createAutomaticEmailScheduledTask($newsletter, $subscriber);
    // new scheduled task should be created
    $task = $this->scheduledTasksRepository->findOneByNewsletter($newsletter);
    $expectedTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addHours(2);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getId())->greaterOrEquals(1);
    expect($task->getPriority())->equals(SendingQueueEntity::PRIORITY_MEDIUM);
    expect($task->getStatus())->equals(SendingQueueEntity::STATUS_SCHEDULED);
    $this->tester->assertEqualDateTimes($expectedTime, $task->getScheduledAt(), 1);
    // task should have 1 associated user
    $subscribers = $task->getSubscribers()->toArray();
    expect($subscribers)->count(1);
    expect($subscribers[0]->getSubscriber())->equals($subscriber);
  }

  public function testItAddsMetaToSendingQueueWhenCreatingAutomaticEmailSendingTask() {
    $newsletter = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $subscriber = (new SubscriberFactory())->create();
    $meta = ['some' => 'value'];

    $this->automaticEmailScheduler->createAutomaticEmailScheduledTask($newsletter, $subscriber, $meta);
    // new queue record should be created with meta data
    $queue = $this->sendingQueuesRepository->findOneBy(['newsletter' => $newsletter]);
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $this->assertEquals($meta, $queue->getMeta());
  }

  public function testItCreatesAutomaticEmailSendingTaskForSegment() {
    $newsletter = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);

    $this->automaticEmailScheduler->createAutomaticEmailScheduledTask($newsletter, null);
    // new scheduled task should be created
    $task = $this->scheduledTasksRepository->findOneByNewsletter($newsletter);
    $expectedTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addHours(2);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getId())->greaterOrEquals(1);
    expect($task->getPriority())->equals(SendingQueueEntity::PRIORITY_MEDIUM);
    expect($task->getStatus())->equals(SendingQueueEntity::STATUS_SCHEDULED);
    $this->tester->assertEqualDateTimes($expectedTime, $task->getScheduledAt(), 1);
    // task should not have any subscribers
    $subscribers = $task->getSubscribers();
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

  public function testItCanCancelMultipleAutomaticEmails() {
    $newsletter = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->newsletterOptionFactory->createMultipleOptions(
      $this->newsletter,
      [
        'group' => 'some_group',
        'event' => 'some_event',
      ]
    );
    $newsletter2 = $this->createAutomaticNewsletter();
    $this->newsletterOptionFactory->createMultipleOptions(
      $newsletter2,
      [
        'group' => 'some_group',
        'event' => 'some_event',
      ]
    );
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $subscriber = (new SubscriberFactory())->create();
    $this->automaticEmailScheduler->createAutomaticEmailScheduledTask($newsletter, $subscriber);
    $this->automaticEmailScheduler->createAutomaticEmailScheduledTask($newsletter2, $subscriber);
    $this->automaticEmailScheduler->cancelAutomaticEmail('some_group', 'some_event', $subscriber);
  }

  public function testItSchedulesAutomaticEmailWhenConditionMatches() {
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
    $expectedTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addHours(2);
    $automaticEmailScheduler = $this->diContainer->get(AutomaticEmailScheduler::class);
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
    $this->tester->assertEqualDateTimes($expectedTime, $task->getScheduledAt(), 1);
  }

  private function createAutomaticNewsletter(): NewsletterEntity {
    $newsletter = $this->newsletterFactory->withActiveStatus()->withAutomaticType()->create();
    $this->newsletterOptionFactory = new NewsletterOptionFactory();
    $this->newsletterOptionFactory->createMultipleOptions(
      $newsletter,
      [
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    return $newsletter;
  }
}
