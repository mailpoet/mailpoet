<?php

namespace MailPoet\Test\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SchedulerTest extends \MailPoetTest {

  /** @var Scheduler */
  private $testee;

  public function _before() {
    parent::_before();
    $this->testee = $this->diContainer->get(Scheduler::class);
  }

  public function testItGetsActiveNewslettersFilteredByTypeAndGroup() {
    $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME);

    // no newsletters with type "notification" should be found
    expect($this->testee->getNewsletters(NewsletterEntity::TYPE_NOTIFICATION))->isEmpty();

    // one newsletter with type "welcome" should be found
    expect($this->testee->getNewsletters(NewsletterEntity::TYPE_WELCOME))->count(1);

    // one automatic email belonging to "test" group should be found
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter->id,
      NewsletterEntity::TYPE_AUTOMATIC,
      [
        NewsletterOptionFieldEntity::NAME_GROUP => 'test',
      ]
    );

    expect($this->testee->getNewsletters(Newsletter::TYPE_WELCOME, 'group_does_not_exist'))->isEmpty();
    expect($this->testee->getNewsletters(Newsletter::TYPE_AUTOMATIC, 'test'))->count(1);
  }

  public function testItCanGetNextRunDate() {
    // it accepts cron syntax and returns next run date
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    expect($this->testee->getNextRunDate('* * * * *'))
      ->equals($currentTime->addMinute()->format('Y-m-d H:i:00'));
    // when invalid CRON expression is used, false response is returned
    expect($this->testee->getNextRunDate('invalid CRON expression'))->false();
  }

  public function testItCanGetPreviousRunDate() {
    // it accepts cron syntax and returns previous run date
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    expect($this->testee->getPreviousRunDate('* * * * *'))
      ->equals($currentTime->subMinute()->format('Y-m-d H:i:00'));
    // when invalid CRON expression is used, false response is returned
    expect($this->testee->getPreviousRunDate('invalid CRON expression'))->false();
  }

  public function testItFormatsDatetimeString() {
    expect($this->testee->formatDatetimeString('April 20, 2016 4pm'))
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
