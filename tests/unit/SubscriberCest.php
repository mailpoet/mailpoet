<?php
use \UnitTester;

class SubscriberCest {

    public function _before() {
      $this->subscriber = \Model::factory('Subscriber')->create();
      $this->subscriber->first_name = 'John';
      $this->subscriber->last_name = 'Mailer';
      $this->subscriber->email = 'john@mailpoet.com';
      $this->subscriber->save();
    }

    public function itCanBeCreated() {
    }

    public function _after() {
    }
}
