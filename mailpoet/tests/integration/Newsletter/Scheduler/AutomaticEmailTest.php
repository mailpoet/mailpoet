<?php

namespace MailPoet\Newsletter\Scheduler;

use Codeception\Util\Fixtures;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionFactory;
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

  public function _before() {
    parent::_before();
    $this->automaticEmailScheduler = $this->diContainer->get(AutomaticEmailScheduler::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);

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
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();

    $this->automaticEmailScheduler->createAutomaticEmailSendingTask($newsletter, $subscriber->id, $meta = null);
    // new scheduled task should be created
    $task = SendingTask::getByNewsletterId($newsletter->getId());
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    expect($task->id)->greaterOrEquals(1);
    expect($task->priority)->equals(SendingQueue::PRIORITY_MEDIUM);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect(Carbon::parse($task->scheduledAt)->format('Y-m-d H:i'))
      ->equals($currentTime->addHours(2)->format('Y-m-d H:i'));
    // task should have 1 associated user
    $subscribers = $task->subscribers()->findMany();
    expect($subscribers)->count(1);
    expect($subscribers[0]->id)->equals($subscriber->id);
  }

  public function testItAddsMetaToSendingQueueWhenCreatingAutomaticEmailSendingTask() {
    $newsletter = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $meta = ['some' => 'value'];

    $this->automaticEmailScheduler->createAutomaticEmailSendingTask($newsletter, $subscriber->id, $meta);
    // new queue record should be created with meta data
    $queue = SendingQueue::where('newsletter_id', $newsletter->getId())->findOne();
    assert($queue instanceof SendingQueue);
    expect($queue->getMeta())->equals($meta);
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
    expect($task->priority)->equals(SendingQueue::PRIORITY_MEDIUM);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
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
    expect(SendingQueue::findMany())->count(0);
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
    expect(SendingQueue::findMany())->count(0);
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
    $result = SendingQueue::findMany();
    expect($result)->count(1);
    expect($result[0]->newsletter_id)->equals($newsletter2->getId());
    // scheduled task should be created
    $task = $result[0]->getTasks()->findOne();
    expect($task->id)->greaterOrEquals(1);
    expect($task->priority)->equals(SendingQueue::PRIORITY_MEDIUM);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect(Carbon::parse($task->scheduledAt)->format('Y-m-d H:i'))
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
