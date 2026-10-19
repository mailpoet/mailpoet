<?php declare(strict_types = 1);

namespace MailPoet\Config;

use Codeception\Stub\Expected;
use MailPoet\Cron\ActionScheduler\Actions\DaemonTrigger;
use MailPoet\Cron\ActionScheduler\ActionSchedulerTestHelper;
use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\DaemonActionSchedulerRunner;
use MailPoet\Entities\SettingEntity;
use MailPoet\Migrator\Migrator;
use MailPoet\Migrator\MigratorException;
use MailPoet\Settings\SettingsController;

require_once __DIR__ . '/../Cron/ActionScheduler/ActionSchedulerTestHelper.php';

class ActivatorCronTest extends \MailPoetTest {

  /** @var Activator */
  private $activator;

  /** @var SettingsController */
  private $settings;

  /** @var DaemonActionSchedulerRunner */
  private $daemonActionSchedulerRunner;

  /** @var ActionSchedulerTestHelper */
  private $actionSchedulerHelper;

  public function _before(): void {
    $this->activator = $this->diContainer->get(Activator::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->daemonActionSchedulerRunner = $this->diContainer->get(DaemonActionSchedulerRunner::class);
    $this->actionSchedulerHelper = new ActionSchedulerTestHelper();
    $this->cleanup();
    $this->daemonActionSchedulerRunner->clearDeactivationFlag();
  }

  public function testProcessActivateReschedulesDaemonTrigger(): void {
    $this->settings->set(CronTrigger::SETTING_CURRENT_METHOD, CronTrigger::METHOD_ACTION_SCHEDULER);

    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(0);

    $this->activator->activate();

    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(1);
    $action = reset($actions);
    $this->assertInstanceOf(\ActionScheduler_Action::class, $action);
    verify($action->get_hook())->equals(DaemonTrigger::NAME);
  }

  public function testProcessActivateDoesNotScheduleWhenMethodIsNotActionScheduler(): void {
    $this->settings->set(CronTrigger::SETTING_CURRENT_METHOD, CronTrigger::METHOD_WORDPRESS);

    $this->activator->activate();

    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(0);
  }

  public function testDeactivationFlagIsClearedAfterActivation(): void {
    $this->settings->set(CronTrigger::SETTING_CURRENT_METHOD, CronTrigger::METHOD_ACTION_SCHEDULER);
    update_option(DaemonActionSchedulerRunner::DEACTIVATION_FLAG_OPTION, time());
    verify($this->daemonActionSchedulerRunner->isDeactivating())->true();

    $this->activator->activate();

    verify($this->daemonActionSchedulerRunner->isDeactivating())->false();
  }

  public function testItRefusesToRunWhileAnotherRequestHoldsTheLock(): void {
    set_transient(Activator::TRANSIENT_ACTIVATE_KEY, '1', 120);
    $activator = $this->getServiceWithOverrides(Activator::class, [
      'migrator' => $this->makeEmpty(Migrator::class, ['run' => Expected::never()]),
    ]);

    try {
      $this->expectException(ActivationInProgressException::class);
      $this->expectExceptionMessage('update is in progress');
      $activator->activate();
    } finally {
      delete_transient(Activator::TRANSIENT_ACTIVATE_KEY);
    }
  }

  public function testItReleasesTheLockAndKeepsTheOldDbVersionWhenAMigrationFails(): void {
    $this->settings->set('db_version', '0.0.1');
    $activator = $this->getServiceWithOverrides(Activator::class, [
      'migrator' => $this->makeEmpty(Migrator::class, [
        'run' => Expected::once(function () {
          throw MigratorException::migrationFailed('Migration_1', new \Exception('Unknown column'));
        }),
      ]),
    ]);

    $failure = null;
    try {
      $activator->activate();
    } catch (MigratorException $e) {
      $failure = $e;
    } finally {
      $dbVersion = $this->settings->get('db_version');
      $this->settings->set('db_version', Env::$version);
    }

    $this->assertInstanceOf(MigratorException::class, $failure);
    verify($failure->getMessage())->stringContainsString('Unknown column');
    verify(get_transient(Activator::TRANSIENT_ACTIVATE_KEY))->false();
    verify($dbVersion)->equals('0.0.1');
  }

  public function testItKnowsWhetherTheDatabaseIsUpToDate(): void {
    $this->settings->set('db_version', Env::$version);
    verify($this->activator->isDbUpToDate())->true();
    verify($this->activator->getDbVersion())->equals(Env::$version);

    $this->settings->set('db_version', '0.0.1');
    verify($this->activator->isDbUpToDate())->false();

    $this->settings->delete('db_version');
    verify($this->activator->isDbUpToDate())->false();
    verify($this->activator->getDbVersion())->null();
    $this->settings->set('db_version', Env::$version);
  }

  public function testRefreshSeesADbVersionAnotherProcessWrote(): void {
    $this->settings->set('db_version', '0.0.1');
    verify($this->activator->isDbUpToDate())->false();

    // a raw write leaves this process's settings cache and Doctrine identity map stale,
    // exactly as a write from the lock-holding process would
    $table = $this->entityManager->getClassMetadata(SettingEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE $table SET value = :value WHERE name = 'db_version'",
      ['value' => Env::$version]
    );
    verify($this->activator->isDbUpToDate())->false();

    $this->activator->refreshDbVersion();
    verify($this->activator->isDbUpToDate())->true();
  }

  private function cleanup(): void {
    global $wpdb;
    $actionsTable = $wpdb->prefix . 'actionscheduler_actions';
    $wpdb->query($wpdb->prepare('TRUNCATE %i', $actionsTable));
  }
}
