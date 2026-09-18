<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use MailPoet\Config\Env;
use MailPoet\Config\SchemaState;
use MailPoet\Entities\SettingEntity;
use MailPoet\Migrator\MigratorException;
use MailPoet\Settings\SettingsController;

class SchemaStateTest extends \MailPoetTest {
  private SettingsController $settings;

  private SchemaState $schemaState;

  public function _before(): void {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    // fresh instance: the container's shared SchemaState would carry a failure across tests
    $this->schemaState = new SchemaState($this->settings);
  }

  public function _after(): void {
    $this->settings->set('db_version', Env::$version);
    parent::_after();
  }

  public function testItIsReadyWhenDbVersionMatchesPluginVersion(): void {
    $this->settings->set('db_version', Env::$version);
    verify($this->schemaState->isReady())->true();
    verify($this->schemaState->getStatus())->equals(SchemaState::STATUS_READY);
    verify($this->schemaState->getMessage())->equals('');
    verify($this->schemaState->getPublicMessage())->equals('');
  }

  public function testItIsNotReadyWhenDbVersionIsBehind(): void {
    $this->settings->set('db_version', '0.0.1');
    verify($this->schemaState->isReady())->false();
    verify($this->schemaState->getStatus())->equals(SchemaState::STATUS_UPDATING);
    verify($this->schemaState->getMessage())->stringContainsString('update is in progress');
    verify($this->schemaState->getPublicMessage())->equals($this->schemaState->getMessage());
  }

  public function testItIsNotReadyWhenDbVersionIsMissing(): void {
    $this->settings->delete('db_version');
    verify($this->schemaState->isReady())->false();
  }

  public function testRefreshSeesADbVersionAnotherProcessWrote(): void {
    $this->settings->set('db_version', '0.0.1');
    verify($this->schemaState->isReady())->false();

    // a raw write leaves this process's settings cache and Doctrine identity map stale,
    // exactly as a write from the lock-holding process would
    $table = $this->entityManager->getClassMetadata(SettingEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE $table SET value = :value WHERE name = 'db_version'",
      ['value' => Env::$version]
    );
    verify($this->schemaState->isReady())->false();

    $this->schemaState->refresh();
    verify($this->schemaState->isReady())->true();
  }

  public function testItReportsAFailedMigrationInDetailToAdminsOnly(): void {
    $this->settings->set('db_version', '0.0.1');
    $failure = MigratorException::migrationFailed('Migration_1', new \Exception('Unknown column wp_mailpoet_x.y'));
    $this->schemaState->markFailed($failure);
    verify($this->schemaState->getStatus())->equals(SchemaState::STATUS_FAILED);
    verify($this->schemaState->getMessage())->stringContainsString('Unknown column wp_mailpoet_x.y');
    verify($this->schemaState->getPublicMessage())->stringNotContainsString('Unknown column');
    verify($this->schemaState->getPublicMessage())->stringContainsString('contact the site administrator');
  }

  public function testAFailureRecordedEarlierDoesNotOutliveASuccessfulUpdate(): void {
    $this->settings->set('db_version', '0.0.1');
    $this->schemaState->markFailed(new \Exception('boom'));
    $this->settings->set('db_version', Env::$version);
    verify($this->schemaState->getStatus())->equals(SchemaState::STATUS_READY);
  }
}
