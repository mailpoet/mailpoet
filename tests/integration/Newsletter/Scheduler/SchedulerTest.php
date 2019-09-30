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
