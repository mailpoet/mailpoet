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
use MailPoetVendor\Idiorm\ORM;

class PagesTest extends \MailPoetTest {
  public $pages;

  private $test_data = [];

  /** @var Subscriber */
  private $subscriber;

  public function _before() {
    parent::_before();
    $this->subscriber = Subscriber::create();
    $this->subscriber->hydrate(Fixtures::get('subscriber_template'));
    $this->subscriber->status = Subscriber::STATUS_UNCONFIRMED;
    $this->subscriber->save();
    $link_tokens = new LinkTokens;
    expect($this->subscriber->getErrors())->false();
    $this->test_data['email'] = $this->subscriber->email;
    $this->test_data['token'] = $link_tokens->getToken($this->subscriber);
    $this->pages = ContainerWrapper::getInstance()->get(Pages::class);
  }

  public function testItConfirmsSubscription() {
    $new_subscriber_notification_sender = Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send' => Stub\Expected::once()]);
    $subscription = $this->pages->init($action = false, $this->test_data, false, false, $new_subscriber_notification_sender);
    $subscription->confirm();
    $confirmed_subscriber = Subscriber::findOne($this->subscriber->id);
    expect($confirmed_subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotConfirmSubscriptionOnDuplicateAttempt() {
    $new_subscriber_notification_sender = Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send' => Stub\Expected::once()]);
    $subscriber = $this->subscriber;
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $subscription = $this->pages->init($action = false, $this->test_data, false, false, $new_subscriber_notification_sender);
    expect($subscription->confirm())->false();
  }

  public function testItSendsWelcomeNotificationUponConfirmingSubscription() {
    $new_subscriber_notification_sender = Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send' => Stub\Expected::once()]);
    $subscription = $this->pages->init($action = false, $this->test_data, false, false, $new_subscriber_notification_sender);
    // create segment
    $segment = Segment::create();
    $segment->hydrate(['name' => 'List #1']);
    $segment->save();
    expect($segment->getErrors())->false();
    // create subscriber->segment relation
    $subscriber_segment = SubscriberSegment::create();
    $subscriber_segment->hydrate(
      [
        'subscriber_id' => $this->subscriber->id,
        'segment_id' => $segment->id,
      ]
    );
    $subscriber_segment->save();
    expect($subscriber_segment->getErrors())->false();

    // create welcome notification newsletter and relevant scheduling options
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    $newsletter_options = [
      'event' => 'segment',
      'segment' => $segment->id,
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1,
    ];
    foreach ($newsletter_options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::create();
      $newsletter_option_field->name = $option;
      $newsletter_option_field->newsletter_type = $newsletter->type;
      $newsletter_option_field->save();
      expect($newsletter_option_field->getErrors())->false();

      $newsletter_option = NewsletterOption::create();
      $newsletter_option->option_field_id = (int)$newsletter_option_field->id;
      $newsletter_option->newsletter_id = $newsletter->id;
      $newsletter_option->value = (string)$value;
      $newsletter_option->save();
      expect($newsletter_option->getErrors())->false();
    }

    // confirm subscription and ensure that welcome email is scheduled
    $subscription->confirm();
    $scheduled_notification = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
      ->findOne();
    expect($scheduled_notification)->notEmpty();
  }

  public function testItUnsubscribes() {
    $pages = $this->pages->init($action = 'unsubscribe', $this->test_data);
    $pages->unsubscribe();
    $updated_subscriber = Subscriber::findOne($this->subscriber->id);
    expect($updated_subscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function testItDoesntUnsubscribeWhenPreviewing() {
    $this->test_data['preview'] = 1;
    $pages = $this->pages->init($action = 'unsubscribe', $this->test_data);
    $pages->unsubscribe();
    $updated_subscriber = Subscriber::findOne($this->subscriber->id);
    expect($updated_subscriber->status)->notEquals(Subscriber::STATUS_UNSUBSCRIBED);
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
