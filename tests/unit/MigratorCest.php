<?php
use \MailPoet\Config\Migrator;

class MigratorCest {
  function _before() {
    $this->migrator = new Migrator();
  }

  function itCanGenerateTheSubscribersSql() {
    $subscriber_sql = $this->migrator->subscribers();
    $expected_table = $this->migrator->prefix . 'subscribers';
    expect($subscriber_sql)->contains($expected_table);
  }

  function _after() {
  }
}
