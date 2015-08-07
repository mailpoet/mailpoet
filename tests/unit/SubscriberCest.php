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
      $this->id = $this->subscriber->id;
    }

    function itCanBeCreated() {
      $subscriber = Subscriber::where('first_name', $this->data['first_name'])->findOne();
      expect($subscriber->id)->notNull();
    }

    function itHasAFirstName() {
      $subscriber = Subscriber::findOne($this->id);
      expect($subscriber->first_name)
        ->equals($this->data['first_name']);
    }

    function itHasALastName() {
      $subscriber = Subscriber::findOne($this->id);
      expect($subscriber->last_name)
        ->equals($this->data['last_name']);
    }

    function itHasAnEmail() {
      $subscriber = Subscriber::findOne($this->id);
      expect($subscriber->email)
        ->equals($this->data['email']);
    }

    function _after() {
      Subscriber::findOne($this->id)->delete();
    }
}
