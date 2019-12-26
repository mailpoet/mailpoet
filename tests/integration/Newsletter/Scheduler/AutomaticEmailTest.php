<?php

namespace MailPoet\Newsletter\Scheduler;

use Codeception\Util\Fixtures;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterPost;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class AutomaticEmailTest extends \MailPoetTest {

  /** @var AutomaticEmailScheduler */
  private $automatic_email_scheduler;

  public function _before() {
    parent::_before();
    $this->automatic_email_scheduler = new AutomaticEmailScheduler;
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
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_AUTOMATIC)->findOne($newsletter->id);
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();

    $this->automatic_email_scheduler->createAutomaticEmailSendingTask($newsletter, $subscriber->id, $meta = null);
    // new scheduled task should be created
    $task = SendingTask::getByNewsletterId($newsletter->id);
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    expect($task->id)->greaterOrEquals(1);
    expect($task->priority)->equals(SendingQueue::PRIORITY_MEDIUM);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect(Carbon::parse($task->scheduled_at)->format('Y-m-d H:i'))
      ->equals($current_time->addHours(2)->format('Y-m-d H:i'));
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
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_AUTOMATIC)->findOne($newsletter->id);
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $meta = ['some' => 'value'];

    $this->automatic_email_scheduler->createAutomaticEmailSendingTask($newsletter, $subscriber->id, $meta);
    // new queue record should be created with meta data
    $queue = SendingQueue::where('newsletter_id', $newsletter->id)->findOne();
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
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_AUTOMATIC)->findOne($newsletter->id);

    $this->automatic_email_scheduler->createAutomaticEmailSendingTask($newsletter, $subscriber = null, $meta = null);
    // new scheduled task should be created
    $task = SendingTask::getByNewsletterId($newsletter->id);
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    expect($task->id)->greaterOrEquals(1);
    expect($task->priority)->equals(SendingQueue::PRIORITY_MEDIUM);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect(Carbon::parse($task->scheduled_at)->format('Y-m-d H:i'))
      ->equals($current_time->addHours(2)->format('Y-m-d H:i'));
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
    $this->automatic_email_scheduler->scheduleAutomaticEmail('group_does_not_exist', 'some_event');
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
    $this->automatic_email_scheduler->scheduleAutomaticEmail('some_group', 'event_does_not_exist');
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItSchedulesAutomaticEmailWhenConditionMatches() {
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    $newsletter_1 = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter_1->id,
      [
        'group' => 'some_group',
        'event' => 'some_event',
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $newsletter_2 = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter_2->id,
      [
        'group' => 'some_group',
        'event' => 'some_event',
        'sendTo' => 'segment',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $condition = function($email) {
      return $email->sendTo === 'segment';
    };

    // email should only be scheduled if it matches condition ("send to segment")
    $this->automatic_email_scheduler->scheduleAutomaticEmail('some_group', 'some_event', $condition);
    $result = SendingQueue::findMany();
    expect($result)->count(1);
    expect($result[0]->newsletter_id)->equals($newsletter_2->id);
    // scheduled task should be created
    $task = $result[0]->getTasks()->findOne();
    expect($task->id)->greaterOrEquals(1);
    expect($task->priority)->equals(SendingQueue::PRIORITY_MEDIUM);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect(Carbon::parse($task->scheduled_at)->format('Y-m-d H:i'))
      ->equals($current_time->addHours(2)->format('Y-m-d H:i'));
  }

  private function _createNewsletter() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_AUTOMATIC;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    return $newsletter;
  }

  private function _createNewsletterOptions($newsletter_id, $options) {
    foreach ($options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::where('name', $option)->findOne();
      if (!$newsletter_option_field) {
        $newsletter_option_field = NewsletterOptionField::create();
        $newsletter_option_field->name = $option;
        $newsletter_option_field->newsletter_type = Newsletter::TYPE_AUTOMATIC;
        $newsletter_option_field->save();
        expect($newsletter_option_field->getErrors())->false();
      }

      $newsletter_option = NewsletterOption::create();
      $newsletter_option->option_field_id = $newsletter_option_field->id;
      $newsletter_option->newsletter_id = $newsletter_id;
      $newsletter_option->value = $value;
      $newsletter_option->save();
      expect($newsletter_option->getErrors())->false();
    }
  }

  public function _after() {
    Carbon::setTestNow();
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterPost::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}
