<?php
use MailPoet\Models\Subscriber;

class SubscriberCest {

  function _before() {
    $this->before_time = time();
    $this->data = array(
      'first_name' => 'John',
      'last_name'  => 'Mailer',
      'email'      => 'john@mailpoet.com'
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

  function _after() {
    ORM::for_table(Subscriber::$_table)
      ->delete_many();
  }
}
