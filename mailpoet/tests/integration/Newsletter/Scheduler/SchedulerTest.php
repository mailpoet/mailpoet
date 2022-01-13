<?php

namespace MailPoet\Test\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class SchedulerTest extends \MailPoetTest {
  public function testItGetsActiveNewslettersFilteredByTypeAndGroup() {
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

  public function testItCanGetNextRunDate() {
    // it accepts cron syntax and returns next run date
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    expect(Scheduler::getNextRunDate('* * * * *'))
      ->equals($currentTime->addMinute()->format('Y-m-d H:i:00'));
    // when invalid CRON expression is used, false response is returned
    expect(Scheduler::getNextRunDate('invalid CRON expression'))->false();
  }

  public function testItCanGetPreviousRunDate() {
    // it accepts cron syntax and returns previous run date
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    expect(Scheduler::getPreviousRunDate('* * * * *'))
      ->equals($currentTime->subMinute()->format('Y-m-d H:i:00'));
    // when invalid CRON expression is used, false response is returned
    expect(Scheduler::getPreviousRunDate('invalid CRON expression'))->false();
  }

  public function testItFormatsDatetimeString() {
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

  private function _createNewsletterOptions($newsletterId, $newsletterType, $options) {
    foreach ($options as $option => $value) {
      $newsletterOptionField = NewsletterOptionField::where('name', $option)->findOne();
      if (!$newsletterOptionField) {
        $newsletterOptionField = NewsletterOptionField::create();
        $newsletterOptionField->name = $option;
        $newsletterOptionField->newsletterType = $newsletterType;
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
