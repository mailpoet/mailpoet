<?php

namespace MailPoet\Newsletter\Scheduler;

use Codeception\Util\Fixtures;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class AutomaticEmailTest extends \MailPoetTest {

  /** @var AutomaticEmailScheduler */
  private $automaticEmailScheduler;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function _before() {
    parent::_before();
    $this->automaticEmailScheduler = $this->diContainer->get(AutomaticEmailScheduler::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
  }

  public function testItCreatesScheduledAutomaticEmailSendingTaskForUser() {
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $newsletter = $this->newslettersRepository->findOneById($newsletter->id);
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
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $newsletter = $this->newslettersRepository->findOneById($newsletter->id);
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
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'sendTo' => 'segment',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $newsletter = $this->newslettersRepository->findOneById($newsletter->id);
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
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'group' => 'some_group',
        'event' => 'some_event',
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );

    // email should not be scheduled when group is not matched
    $this->automaticEmailScheduler->scheduleAutomaticEmail('group_does_not_exist', 'some_event');
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItDoesNotScheduleAutomaticEmailWhenEventDoesNotMatch() {
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'group' => 'some_group',
        'event' => 'some_event',
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );

    // email should not be scheduled when event is not matched
    $this->automaticEmailScheduler->scheduleAutomaticEmail('some_group', 'event_does_not_exist');
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItSchedulesAutomaticEmailWhenConditionMatches() {
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $newsletter1 = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter1->id,
      [
        'group' => 'some_group',
        'event' => 'some_event',
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $newsletter2 = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter2->id,
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
    expect($result[0]->newsletter_id)->equals($newsletter2->id);
    // scheduled task should be created
    $task = $result[0]->getTasks()->findOne();
    expect($task->id)->greaterOrEquals(1);
    expect($task->priority)->equals(SendingQueue::PRIORITY_MEDIUM);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect(Carbon::parse($task->scheduledAt)->format('Y-m-d H:i'))
      ->equals($currentTime->addHours(2)->format('Y-m-d H:i'));
  }

  private function _createNewsletter() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_AUTOMATIC;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    return $newsletter;
  }

  private function _createNewsletterOptions($newsletterId, $options) {
    foreach ($options as $option => $value) {
      $newsletterOptionField = NewsletterOptionField::where('name', $option)->findOne();
      if (!$newsletterOptionField) {
        $newsletterOptionField = NewsletterOptionField::create();
        $newsletterOptionField->name = $option;
        $newsletterOptionField->newsletterType = Newsletter::TYPE_AUTOMATIC;
        $newsletterOptionField->save();
        expect($newsletterOptionField->getErrors())->false();
      }

      $newsletterOption = NewsletterOption::create();
      $newsletterOption->optionFieldId = (int)$newsletterOptionField->id;
      $newsletterOption->newsletterId = $newsletterId;
      $newsletterOption->value = $value;
      $newsletterOption->save();
      expect($newsletterOption->getErrors())->false();
    }
  }

  public function _after() {
    Carbon::setTestNow();
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    $this->truncateEntity(NewsletterPostEntity::class);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}
