<?php
use MailPoet\Models\Subscriber;

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
    $this->subscriber->save();
  }

  function itCanBeCreated() {
    $subscriber = Subscriber::where('email', $this->data['email'])
      ->findOne();
    expect($subscriber->id)->notNull();
  }

  function itHasAFirstName() {
    $subscriber = Subscriber::where('email', $this->data['email'])
      ->findOne();
    expect($subscriber->first_name)->equals($this->data['first_name']);
  }

  function itHasALastName() {
    $subscriber = Subscriber::where('email', $this->data['email'])
      ->findOne();
    expect($subscriber->last_name)->equals($this->data['last_name']);
  }

  function itHasAnEmail() {
    $subscriber = Subscriber::where('email', $this->data['email'])
      ->findOne();
    expect($subscriber->email)->equals($this->data['email']);
  }

  function emailMustBeUnique() {
    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->hydrate($this->data);
    $conflicted = false;
    try {
      $conflict_subscriber->save();
    } catch (Exception $e) {
      $conflicted = true;
    }
    expect($conflicted)->equals(true);
  }

  function emailShouldValidate() {
    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->validateField('email', '');
    expect($conflict_subscriber->getValidationErrors()[0])->equals('email_is_blank');

    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->validateField('email', 'some @ email . com');
    expect($conflict_subscriber->getValidationErrors()[0])->equals('email_is_invalid');
  }

  function firstNameShouldValidate() {
    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->validateField('first_name', '');
    expect($conflict_subscriber->getValidationErrors()[0])->equals('first_name_is_blank');

    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->validateField('first_name', 'a');
    expect($conflict_subscriber->getValidationErrors()[0])->equals('first_name_is_short');
  }


  function lastNameShouldValidate() {
    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->validateField('last_name', '');
    expect($conflict_subscriber->getValidationErrors()[0])->equals('last_name_is_blank');

    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->validateField('last_name', 'a');
    expect($conflict_subscriber->getValidationErrors()[0])->equals('last_name_is_short');
  }

  function itHasACreatedAtOnCreation() {
    $subscriber = Subscriber::where('email', $this->data['email'])
      ->findOne();
    $time_difference = strtotime($subscriber->created_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itHasAnUpdatedAtOnCreation() {
    $subscriber = Subscriber::where('email', $this->data['email'])
      ->findOne();
    $time_difference = strtotime($subscriber->updated_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itKeepsTheCreatedAtOnUpdate() {
    $subscriber = Subscriber::where('email', $this->data['email'])
      ->findOne();

    $old_created_at = $subscriber->created_at;

    $subscriber->first_name = 'New Name';
    $subscriber->save();

    expect($old_created_at)->equals($subscriber->created_at);
  }

  function itUpdatesTheUpdatedAtOnUpdate() {
    $subscriber = Subscriber::where('email', $this->data['email'])
      ->findOne();

    $update_time = time();
    $subscriber->first_name = 'New Name';
    $subscriber->save();

    $time_difference = strtotime($subscriber->updated_at) >= $update_time;

    expect($time_difference)->equals(true);
  }

  function _after() {
    $deloeteSubscribers = ORM::for_table(Subscriber::$_table)
      ->delete_many();
  }

}
