<?php
use \UnitTester;
use \MailPoet\Config\Migrator;

class MigratorCest {
  function _before() {
    $this->migrator = new Migrator();
  }

  function itCanGenerateTheSubscriberSql() {
    $subscriber_sql = $this->migrator->subscriber();
    $expected_table = $this->migrator->prefix . 'subscriber';
    expect($subscriber_sql)->contains($expected_table);
  }

  function _after() {
  }
}
