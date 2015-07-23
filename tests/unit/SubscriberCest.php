<?php
use \UnitTester;
use \MailPoet\Models\Subscriber;

class SubscriberCest {

    public function _before() {
      $this->subscriber = new Subscriber();
    }

    public function itCanBeCreated() {
      expect($this->subscriber->name)->equals('Name');
    }

    public function _after() {
    }
}
