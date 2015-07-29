<?php
use \UnitTester;
use \MailPoet\Config\Migrator;

class MigratorCest {
  public function _before() {
    $migrator = new Migrator();
  }

  public function itCanBeCreated() {
  }

  public function _after() {
  }
}
