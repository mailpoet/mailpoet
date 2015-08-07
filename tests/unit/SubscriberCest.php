<?php
use \UnitTester;
use \MailPoet\Models\Subscriber;

class SubscriberCest {

    function _before() {
      $this->data = array(
        'first_name' => 'John',
        'last_name' => 'Mailer',
        'email' => 'john@mailpoet.com'
      );

      $this->subscriber = Subscriber::create();

      $this
        ->subscriber
        ->first_name = $this->data['first_name'];

      $this
        ->subscriber
        ->last_name = $this->data['last_name'];

      $this->subscriber->email = $this->data['email'];

      $this->subscriber->save();
    }

    function itCanBeCreated() {
      $subscriber = Subscriber::where('first_name', $this->data['first_name'])->findOne();
      expect($subscriber->id)->notNull();
    }

    function itHasAFirstName() {
      $subscriber = Subscriber::where('first_name', $this->data['first_name'])->findOne();
      expect($subscriber->first_name)
        ->equals($this->data['first_name']);
    }

    function itHasALastName() {
      $subscriber = Subscriber::where('first_name', $this->data['first_name'])->findOne();
      expect($subscriber->last_name)
        ->equals($this->data['last_name']);
    }

    function itHasAnEmail() {
      $subscriber = Subscriber::where('first_name', $this->data['first_name'])->findOne();
      expect($subscriber->email)
        ->equals($this->data['email']);
    }

    function _after() {
      $subscriber = Subscriber::where('first_name', $this->data['first_name'])->findOne()->delete();
    }
}
