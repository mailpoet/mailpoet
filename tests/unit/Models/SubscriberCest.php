<?php
use MailPoet\Models\Subscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SubscriberSegment;

class SubscriberCest {

  function _before() {
    $this->before_time = time();
    $this->data = array(
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com'
    );

    $this->subscriber = Subscriber::create();
    $this->subscriber->hydrate($this->data);
    $this->saved = $this->subscriber->save();
  }

  function itCanBeCreated() {
    expect($this->saved)->equals(true);
  }

  function itHasAFirstName() {
    $subscriber =
      Subscriber::where('email', $this->data['email'])
        ->findOne();
    expect($subscriber->first_name)
      ->equals($this->data['first_name']);
  }

  function itHasALastName() {
    $subscriber =
      Subscriber::where('email', $this->data['email'])
        ->findOne();
    expect($subscriber->last_name)
      ->equals($this->data['last_name']);
  }

  function itHasAnEmail() {
    $subscriber =
      Subscriber::where('email', $this->data['email'])
        ->findOne();
    expect($subscriber->email)
      ->equals($this->data['email']);
  }

  function emailMustBeUnique() {
    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->hydrate($this->data);
    $saved = $conflict_subscriber->save();
    expect($saved)->equals(false);
  }

  function itHasAStatus() {
    $subscriber =
      Subscriber::where('email', $this->data['email'])
      ->findOne();

    expect($subscriber->status)->equals('unconfirmed');
  }

  function itCanChangeStatus() {
    $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
    $subscriber->status = 'subscribed';
    expect($subscriber->save())->equals(true);

    $subscriber_updated = Subscriber::where(
      'email',
      $this->data['email']
    )->findOne();
    expect($subscriber_updated->status)->equals('subscribed');
  }

  function itHasASearchFilter() {
    $subscriber = Subscriber::filter('search', 'john')->findOne();
    expect($subscriber->first_name)->equals($this->data['first_name']);

    $subscriber = Subscriber::filter('search', 'mailer')->findOne();
    expect($subscriber->last_name)->equals($this->data['last_name']);

    $subscriber = Subscriber::filter('search', 'mailpoet')->findOne();
    expect($subscriber->email)->equals($this->data['email']);
  }

  function itHasAGroupFilter() {
    $subscribers = Subscriber::filter('group', 'unconfirmed')->findMany();
    foreach($subscribers as $subscriber) {
      expect($subscriber->status)->equals('unconfirmed');
    }

    $subscribers = Subscriber::filter('group', 'subscribed')->findMany();
    foreach($subscribers as $subscriber) {
      expect($subscriber->status)->equals('subscribed');
    }

    $subscribers = Subscriber::filter('group', 'unsubscribed')->findMany();
    foreach($subscribers as $subscriber) {
      expect($subscriber->status)->equals('unsubscribed');
    }
  }

  function itCanHaveASegment() {
    $segmentData = array(
      'name' => 'some name'
    );

    $segment = Segment::create();
    $segment->hydrate($segmentData);
    $segment->save();
    $association = SubscriberSegment::create();
    $association->subscriber_id = $this->subscriber->id;
    $association->segment_id = $segment->id;
    $association->save();

    $subscriber = Subscriber::find_one($this->subscriber->id);
    $subscriberSegment = $subscriber->segments()
      ->find_one();
    expect($subscriberSegment->id)->equals($segment->id);
  }

  function _after() {
    ORM::for_table(Subscriber::$_table)
      ->delete_many();
    ORM::for_table(Segment::$_table)
      ->delete_many();
    ORM::for_table(SubscriberSegment::$_table)
      ->delete_many();
  }
}
