<?php

namespace MailPoet\Test\Config;

use MailPoet\Config\Migrator;

class MigratorTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    $this->migrator = new Migrator();
  }

  function testItCanGenerateTheSubscribersSql() {
    $subscriber_sql = $this->migrator->subscribers();
    $expected_table = $this->migrator->prefix . 'subscribers';
    expect($subscriber_sql)->contains($expected_table);
  }

  function testItDoesNotMigrateWhenDatabaseIsUpToDate() {
    $changes = $this->migrator->up();
    $this->assertEmpty(
      $changes,
      "Expected no migrations. However, the following changes are planned:\n\t" . implode($changes, "\n\t")
    );
  }

  function _after() {
  }
}
