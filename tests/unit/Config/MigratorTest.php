<?php
use \MailPoet\Config\Migrator;

class MigratorTest extends MailPoetTest {
  function _before() {
    $this->migrator = new Migrator();
  }

  function testItCanGenerateTheSubscribersSql() {
    $subscriber_sql = $this->migrator->subscribers();
    $expected_table = $this->migrator->prefix . 'subscribers';
    expect($subscriber_sql)->contains($expected_table);
  }

  function _after() {
  }
}
