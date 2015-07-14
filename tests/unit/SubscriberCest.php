<?php
use \UnitTester;

class SubscriberCest {

  public function _before() {
    $this->subscriber = true;
  }

  public function _after() {
  }

  public function test() {
    expect($this->subscriber)->equals(true);
  }
}
