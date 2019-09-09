<?php
namespace MailPoet\Test\Newsletter\Scheduler;

use Carbon\Carbon;
use Codeception\Util\Fixtures;
use MailPoet\Config\Hooks;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterPost;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Posts as WPPosts;

class SchedulerTest extends \MailPoetTest {
  function testItSetsConstants() {
    expect(Scheduler::SECONDS_IN_HOUR)->notEmpty();
    expect(Scheduler::LAST_WEEKDAY_FORMAT)->notEmpty();
    expect(Scheduler::WORDPRESS_ALL_ROLES)->notEmpty();
    expect(Scheduler::INTERVAL_IMMEDIATELY)->notEmpty();
    expect(Scheduler::INTERVAL_IMMEDIATE)->notEmpty();
    expect(Scheduler::INTERVAL_DAILY)->notEmpty();
    expect(Scheduler::INTERVAL_WEEKLY)->notEmpty();
    expect(Scheduler::INTERVAL_MONTHLY)->notEmpty();
    expect(Scheduler::INTERVAL_NTHWEEKDAY)->notEmpty();
  }

  function testItGetsActiveNewslettersFilteredByTypeAndGroup() {
    $this->_createNewsletter($type = Newsletter::TYPE_WELCOME);

    // no newsletters with type "notification" should be found
    expect(Scheduler::getNewsletters(Newsletter::TYPE_NOTIFICATION))->isEmpty();

    // one newsletter with type "welcome" should be found
    expect(Scheduler::getNewsletters(Newsletter::TYPE_WELCOME))->count(1);

    // one automatic email belonging to "test" group should be found
    $newsletter = $this->_createNewsletter($type = Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_AUTOMATIC,
      [
        'group' => 'test',
      ]
    );

    expect(Scheduler::getNewsletters(Newsletter::TYPE_AUTOMATIC, 'group_does_not_exist'))->isEmpty();
    expect(Scheduler::getNewsletters(Newsletter::TYPE_WELCOME, 'test'))->count(1);
  }

  function testItCanGetNextRunDate() {
    // it accepts cron syntax and returns next run date
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    expect(Scheduler::getNextRunDate('* * * * *'))
      ->equals($current_time->addMinute()->format('Y-m-d H:i:00'));
    // when invalid CRON expression is used, false response is returned
    expect(Scheduler::getNextRunDate('invalid CRON expression'))->false();
  }

  function testItCanGetPreviousRunDate() {
    // it accepts cron syntax and returns previous run date
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    expect(Scheduler::getPreviousRunDate('* * * * *'))
      ->equals($current_time->subMinute()->format('Y-m-d H:i:00'));
    // when invalid CRON expression is used, false response is returned
    expect(Scheduler::getPreviousRunDate('invalid CRON expression'))->false();
  }

  function testItFormatsDatetimeString() {
    expect(Scheduler::formatDatetimeString('April 20, 2016 4pm'))
      ->equals('2016-04-20 16:00:00');
  }

  function testItCreatesScheduledAutomaticEmailSendingTaskForUser() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_AUTOMATIC,
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

    Scheduler::createAutomaticEmailSendingTask($newsletter, $subscriber->id, $meta = null);
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

  function testItAddsMetaToSendingQueueWhenCreatingAutomaticEmailSendingTask() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_AUTOMATIC,
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

    Scheduler::createAutomaticEmailSendingTask($newsletter, $subscriber->id, $meta);
    // new queue record should be created with meta data
    $queue = SendingQueue::where('newsletter_id', $newsletter->id)->findOne();
    expect($queue->getMeta())->equals($meta);
  }

  function testItCreatesAutomaticEmailSendingTaskForSegment() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_AUTOMATIC,
      [
        'sendTo' => 'segment',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_AUTOMATIC)->findOne($newsletter->id);

    Scheduler::createAutomaticEmailSendingTask($newsletter, $subscriber = null, $meta = null);
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

  function testItDoesNotScheduleAutomaticEmailWhenGroupDoesNotMatch() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_AUTOMATIC,
      [
        'group' => 'some_group',
        'event' => 'some_event',
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );

    // email should not be scheduled when group is not matched
    Scheduler::scheduleAutomaticEmail('group_does_not_exist', 'some_event');
    expect(SendingQueue::findMany())->count(0);
  }

  function testItDoesNotScheduleAutomaticEmailWhenEventDoesNotMatch() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_AUTOMATIC,
      [
        'group' => 'some_group',
        'event' => 'some_event',
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );

    // email should not be scheduled when event is not matched
    Scheduler::scheduleAutomaticEmail('some_group', 'event_does_not_exist');
    expect(SendingQueue::findMany())->count(0);
  }

  function testItSchedulesAutomaticEmailWhenConditionMatches() {
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    $newsletter_1 = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter_1->id,
      Newsletter::TYPE_AUTOMATIC,
      [
        'group' => 'some_group',
        'event' => 'some_event',
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $newsletter_2 = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter_2->id,
      Newsletter::TYPE_AUTOMATIC,
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
    Scheduler::scheduleAutomaticEmail('some_group', 'some_event', $condition);
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

  private function _createNewsletter(
    $type = Newsletter::TYPE_NOTIFICATION,
    $status = Newsletter::STATUS_ACTIVE
  ) {
    $newsletter = Newsletter::create();
    $newsletter->type = $type;
    $newsletter->status = $status;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    return $newsletter;
  }

  private function _createNewsletterOptions($newsletter_id, $newsletter_type, $options) {
    foreach ($options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::where('name', $option)->findOne();
      if (!$newsletter_option_field) {
        $newsletter_option_field = NewsletterOptionField::create();
        $newsletter_option_field->name = $option;
        $newsletter_option_field->newsletter_type = $newsletter_type;
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

  function _after() {
    Carbon::setTestNow();
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterPost::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}
