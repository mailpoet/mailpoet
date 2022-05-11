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
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionFactory;
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
    $newsletterFactory = new NewsletterFactory();
    $newsletterOptionFactory = new NewsletterOptionFactory();

    $newsletterFactory->withWelcomeTypeForSegment()->withActiveStatus()->create();

    // no newsletters with type "notification" should be found
    expect($this->testee->getNewsletters(NewsletterEntity::TYPE_NOTIFICATION))->isEmpty();

    // one newsletter with type "welcome" should be found
    expect($this->testee->getNewsletters(NewsletterEntity::TYPE_WELCOME))->count(1);

    // one automatic email belonging to "test" group should be found
    $newsletter = $newsletterFactory->withAutomaticType()->withActiveStatus()->create();
    $newsletterOptionFactory->create($newsletter, 'group', 'test');

    expect($this->testee->getNewsletters(NewsletterEntity::TYPE_AUTOMATIC, 'group_does_not_exist'))->isEmpty();
    expect($this->testee->getNewsletters(NewsletterEntity::TYPE_WELCOME, 'test'))->count(1);
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

