<?php declare(strict_types = 1);

namespace MailPoet\Test\Migrations\Db;

use MailPoet\Entities\LogEntity;
use MailPoet\Migrations\Db\Migration_20250926_153050_Db;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Log;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20250926_153050_Db_Test extends \MailPoetTest {

  /** @var Migration_20250926_153050_Db */
  private $migration;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20250926_153050_Db($this->diContainer);
    $this->settings = $this->diContainer->get(SettingsController::class);
  }

  public function testUpdatesLoggingLevelFromEverythingToErrors() {
    // Set logging to 'everything'
    $this->settings->set('logging', 'everything');
    verify($this->settings->fetch('logging'))->equals('everything');

    // Run migration
    $this->migration->run();

    // Verify logging is now set to 'errors'
    verify($this->settings->fetch('logging'))->equals('errors');
  }

  public function testDoesNotUpdateLoggingLevelWhenNotEverything() {
    // Set logging to 'nothing'
    $this->settings->set('logging', 'nothing');
    verify($this->settings->fetch('logging'))->equals('nothing');

    // Run migration
    $this->migration->run();

    // Verify logging remains 'nothing'
    verify($this->settings->fetch('logging'))->equals('nothing');
  }

  public function testTruncatesLogTableAndSetsFlagOnFirstRun() {
    // Create some log entries
    $logFactory = new Log();
    $logFactory->create();
    $logFactory->create();

    // Verify logs exist
    $logCount = $this->entityManager->getRepository(LogEntity::class)->count([]);
    verify($logCount)->equals(2);

    // Ensure pruning flag doesn't exist
    $this->settings->delete('log_table_pruned_migration_20250926');

    // Run migration
    $this->migration->run();

    // Verify logs are truncated
    $logCount = $this->entityManager->getRepository(LogEntity::class)->count([]);
    verify($logCount)->equals(0);

    // Verify flag is set
    verify($this->settings->fetch('log_table_pruned_migration_20250926'))->equals('1');
  }

  public function testDoesNotTruncateLogTableOnSecondRun() {
    // Set pruning flag to indicate already pruned
    $this->settings->set('log_table_pruned_migration_20250926', '1');

    // Create some log entries
    $logFactory = new Log();
    $logFactory->create();
    $logFactory->create();

    // Verify logs exist
    $logCount = $this->entityManager->getRepository(LogEntity::class)->count([]);
    verify($logCount)->equals(2);

    // Run migration
    $this->migration->run();

    // Verify logs are NOT truncated
    $logCount = $this->entityManager->getRepository(LogEntity::class)->count([]);
    verify($logCount)->equals(2);

    // Verify flag remains set
    verify($this->settings->fetch('log_table_pruned_migration_20250926'))->equals('1');
  }

  public function testCompleteScenarioWithLoggingUpdateAndLogPruning() {
    // Set up initial state
    $this->settings->set('logging', 'everything');

    $logFactory = new Log();
    $logFactory->create();
    $logFactory->create();
    $logFactory->create();

    // Verify initial state
    verify($this->settings->fetch('logging'))->equals('everything');
    $logCount = $this->entityManager->getRepository(LogEntity::class)->count([]);
    verify($logCount)->equals(3);

    // Run migration
    $this->migration->run();

    // Verify both operations completed
    verify($this->settings->fetch('logging'))->equals('errors');
    $logCount = $this->entityManager->getRepository(LogEntity::class)->count([]);
    verify($logCount)->equals(0);
    verify($this->settings->fetch('log_table_pruned_migration_20250926'))->equals('1');
  }
}
