<?php
use MailPoet\Models\Subscriber;

class SubscriberCest {

  function _before() {
    $this->data = array('first_name' => 'John', 'last_name' => 'Mailer', 'email' => 'john@mailpoet.com');

    // clean up after previously failed test
    $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
    if ($subscriber !== false) {
      $subscriber->delete();
    }

    $this->subscriber = Subscriber::create();

    $this->subscriber->first_name = $this->data['first_name'];

    $this->subscriber->last_name = $this->data['last_name'];

    $this->subscriber->email = $this->data['email'];

    $this->subscriber->save();
  }

  function itCanBeCreated() {
    $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
    expect($subscriber->id)->notNull();
  }

  function itHasAFirstName() {
    $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
    expect($subscriber->first_name)->equals($this->data['first_name']);
  }

  function itHasALastName() {
    $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
    expect($subscriber->last_name)->equals($this->data['last_name']);
  }

  function itHasAnEmail() {
    $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
    expect($subscriber->email)->equals($this->data['email']);
  }

  function emailMustBeUnique() {
    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->first_name = 'First';
    $conflict_subscriber->last_name = 'Last';
    $conflict_subscriber->email = $this->data['email'];
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
    expect($conflict_subscriber->getValidationErrors()[0])->equals('validation_email_blank');

    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->validateField('email', 'some @ email . com');
    expect($conflict_subscriber->getValidationErrors()[0])->equals('validation_email_invalid');
  }

  function firstNameShouldValidate() {
    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->validateField('first_name', '');
    expect($conflict_subscriber->getValidationErrors()[0])->equals('validation_first_name_blank');

    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->validateField('first_name', 'a');
    expect($conflict_subscriber->getValidationErrors()[0])->equals('validation_first_name_length');
  }
  
  
  function lastNameShouldValidate() {
    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->validateField('last_name', '');
    expect($conflict_subscriber->getValidationErrors()[0])->equals('validation_last_name_blank');
    
    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->validateField('last_name', 'a');
    expect($conflict_subscriber->getValidationErrors()[0])->equals('validation_last_name_length');
  }

  function _after() {
    $subscriber = Subscriber::where('email', $this->data['email'])->findOne()->delete();
  }

}
