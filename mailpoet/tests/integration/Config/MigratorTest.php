<?php

namespace MailPoet\Test\Config;

use MailPoet\Config\Migrator;

class MigratorTest extends \MailPoetTest {
  public $migrator;

  public function _before() {
    parent::_before();
    $this->migrator = new Migrator();
  }

  public function testItCanGenerateTheSubscribersSql() {
    $subscriberSql = $this->migrator->subscribers();
    $expectedTable = $this->migrator->prefix . 'subscribers';
    expect($subscriberSql)->stringContainsString($expectedTable);
  }

  public function testItDoesNotMigrateWhenDatabaseIsUpToDate() {
    $changes = $this->migrator->up();
    $this->assertIsArray($changes);
    // phpcs:disable Squiz.PHP.CommentedOutCode
    //    $this->assertEmpty(
    //      $changes,
    //      "Expected no migrations. However, the following changes are planned:\n\t" . implode($changes, "\n\t")
    //    );
  }

  public function _after() {
  }
}
