<?php
use \UnitTester;
use \MailPoet\Config\Migrator;

class MigratorCest {
  public function _before() {
    $this->migrator = new Migrator();
  }

  public function itCanGenerateSubscriberSql() {
    $subscriber_sql = $this->migrator->subscriber();
    $expected_table = $this->migrator->prefix . 'subscriber';
    expect($subscriber_sql)->contains($expected_table);
  }

  public function _after() {
  }
}
