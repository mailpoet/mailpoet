<?php

namespace MailPoet\Test\Subscription;

use Codeception\Stub;
use Codeception\Util\Fixtures;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscription\Pages;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class PagesTest extends \MailPoetTest {
  public $pages;

  private $testData = [];

  /** @var Subscriber */
  private $subscriber;

  public function _before() {
    parent::_before();
    $this->subscriber = Subscriber::create();
    $this->subscriber->hydrate(Fixtures::get('subscriber_template'));
    $this->subscriber->status = Subscriber::STATUS_UNCONFIRMED;
    $this->subscriber->save();
    $linkTokens = new LinkTokens;
    expect($this->subscriber->getErrors())->false();
    $this->testData['email'] = $this->subscriber->email;
    $this->testData['token'] = $linkTokens->getToken($this->subscriber);
    $this->pages = ContainerWrapper::getInstance()->get(Pages::class);
  }

  public function testItConfirmsSubscription() {
    $newSubscriberNotificationSender = Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send' => Stub\Expected::once()]);
    $subscription = $this->pages->init($action = false, $this->testData, false, false, $newSubscriberNotificationSender);
    $subscription->confirm();
    $confirmedSubscriber = Subscriber::findOne($this->subscriber->id);
    expect($confirmedSubscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($confirmedSubscriber->lastSubscribedAt)->greaterOrEquals(Carbon::createFromTimestamp((int)current_time('timestamp'))->subSecond(1));
    expect($confirmedSubscriber->lastSubscribedAt)->lessOrEquals(Carbon::createFromTimestamp((int)current_time('timestamp'))->addSecond(1));
  }

  public function testItDoesNotConfirmSubscriptionOnDuplicateAttempt() {
    $newSubscriberNotificationSender = Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send' => Stub\Expected::once()]);
    $subscriber = $this->subscriber;
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $subscription = $this->pages->init($action = false, $this->testData, false, false, $newSubscriberNotificationSender);
    expect($subscription->confirm())->false();
  }

  public function testItSendsWelcomeNotificationUponConfirmingSubscription() {
    $newSubscriberNotificationSender = Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send' => Stub\Expected::once()]);
    $subscription = $this->pages->init($action = false, $this->testData, false, false, $newSubscriberNotificationSender);
    // create segment
    $segment = Segment::create();
    $segment->hydrate(['name' => 'List #1']);
    $segment->save();
    expect($segment->getErrors())->false();
    // create subscriber->segment relation
    $subscriberSegment = SubscriberSegment::create();
    $subscriberSegment->hydrate(
      [
        'subscriber_id' => $this->subscriber->id,
        'segment_id' => $segment->id,
      ]
    );
    $subscriberSegment->save();
    expect($subscriberSegment->getErrors())->false();

    // create welcome notification newsletter and relevant scheduling options
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    $newsletterOptions = [
      'event' => 'segment',
      'segment' => $segment->id,
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1,
    ];
    foreach ($newsletterOptions as $option => $value) {
      $newsletterOptionField = NewsletterOptionField::create();
      $newsletterOptionField->name = $option;
      $newsletterOptionField->newsletterType = $newsletter->type;
      $newsletterOptionField->save();
      expect($newsletterOptionField->getErrors())->false();

      $newsletterOption = NewsletterOption::create();
      $newsletterOption->optionFieldId = (int)$newsletterOptionField->id;
      $newsletterOption->newsletterId = $newsletter->id;
      $newsletterOption->value = (string)$value;
      $newsletterOption->save();
      expect($newsletterOption->getErrors())->false();
    }

    // confirm subscription and ensure that welcome email is scheduled
    $subscription->confirm();
    $scheduledNotification = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
      ->findOne();
    expect($scheduledNotification)->notEmpty();
  }

  public function testItUnsubscribes() {
    $pages = $this->pages->init($action = 'unsubscribe', $this->testData);
    $pages->unsubscribe();
    $updatedSubscriber = Subscriber::findOne($this->subscriber->id);
    expect($updatedSubscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function testItDoesntUnsubscribeWhenPreviewing() {
    $this->testData['preview'] = 1;
    $pages = $this->pages->init($action = 'unsubscribe', $this->testData);
    $pages->unsubscribe();
    $updatedSubscriber = Subscriber::findOne($this->subscriber->id);
    expect($updatedSubscriber->status)->notEquals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
  }
}
