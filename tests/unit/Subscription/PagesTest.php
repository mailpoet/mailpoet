<?php
namespace MailPoet\Test\Subscription;

use Codeception\Util\Fixtures;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscription\Pages;

class PagesTest extends \MailPoetTest {
  function _before() {
    $this->subscriber = Subscriber::create();
    $this->subscriber->hydrate(Fixtures::get('subscriber_template'));
    $this->subscriber->status = Subscriber::STATUS_UNCONFIRMED;
    $this->subscriber->save();
    expect($this->subscriber->getErrors())->false();
    $this->data['email'] = $this->subscriber->email;
    $this->data['token'] = Subscriber::generateToken($this->subscriber->email);
    $this->subscription = new Pages($action = false, $this->data);
  }

  function testItConfirmsSubscription() {
    $this->subscription->confirm();
    $confirmed_subscriber = Subscriber::findOne($this->subscriber->id);
    expect($confirmed_subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  function testItDoesNotConfirmSubscriptionOnDuplicateAttempt() {
    $subscriber = $this->subscriber;
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $subscription = new Pages($action = false, $this->data);
    expect($subscription->confirm())->false();
  }

  function testItSendsWelcomeNotificationUponConfirmingSubscription() {
    // create segment
    $segment = Segment::create();
    $segment->hydrate(array('name' => 'List #1'));
    $segment->save();
    expect($segment->getErrors())->false();
    // create subscriber->segment relation
    $subscriber_segment = SubscriberSegment::create();
    $subscriber_segment->hydrate(
      array(
        'subscriber_id' => $this->subscriber->id,
        'segment_id' => $segment->id
      )
    );
    $subscriber_segment->save();
    expect($subscriber_segment->getErrors())->false();

    // create welcome notification newsletter and relevant scheduling options
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    $newsletter_options = array(
      'event' => 'segment',
      'segment' => $segment->id,
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1
    );
    foreach($newsletter_options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::create();
      $newsletter_option_field->name = $option;
      $newsletter_option_field->newsletter_type = $newsletter->type;
      $newsletter_option_field->save();
      expect($newsletter_option_field->getErrors())->false();

      $newsletter_option = NewsletterOption::create();
      $newsletter_option->option_field_id = $newsletter_option_field->id;
      $newsletter_option->newsletter_id = $newsletter->id;
      $newsletter_option->value = $value;
      $newsletter_option->save();
      expect($newsletter_option->getErrors())->false();
    }

    // confirm subscription and ensure that welcome email is scheduled
    $this->subscription->confirm();
    $scheduled_notification = SendingQueue::where('newsletter_id', $newsletter->id)
      ->where('status', SendingQueue::STATUS_SCHEDULED)
      ->findOne();
    expect($scheduled_notification)->notEmpty();
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
  }
}
