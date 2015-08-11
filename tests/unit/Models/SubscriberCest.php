<?php
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
      $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
      expect($subscriber->id)->notNull();
    }

    function itHasAFirstName() {
      $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
      expect($subscriber->first_name)
        ->equals($this->data['first_name']);
    }

    function itHasALastName() {
      $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
      expect($subscriber->last_name)
        ->equals($this->data['last_name']);
    }

    function itHasAnEmail() {
      $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
      expect($subscriber->email)
        ->equals($this->data['email']);
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

    function _after() {
      $subscriber = Subscriber::where('email', $this->data['email'])->findOne()->delete();
    }
}
