<?php
use \UnitTester;
use \MailPoet\Config\Migrator;

class MigratorCest {
  public function _before() {
    $this->migrator = new Migrator();
  }

  public function itCreatesTheSubscriberTable() {
    // Can't be tested because of WordPress.
  }

  public function _after() {
  }
}
