<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\Cron\ActionScheduler\Actions\DaemonTrigger;
use MailPoet\Cron\ActionScheduler\ActionScheduler as CronActionScheduler;
use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\DaemonActionSchedulerRunner;
use MailPoet\Migrator\Migrator;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\DBAL\Connection;

class ActivatorTest extends \MailPoetUnitTest {
  public function testRefreshCronActionsDefersActionSchedulerCallsUntilInitialized(): void {
    $settings = $this->createMock(SettingsController::class);
    $settings->expects($this->once())
      ->method('get')
      ->with(CronTrigger::SETTING_NAME . '.method')
      ->willReturn(CronTrigger::METHOD_ACTION_SCHEDULER);

    $cronActionScheduler = $this->createMock(CronActionScheduler::class);
    $daemonActionSchedulerRunner = $this->createMock(DaemonActionSchedulerRunner::class);
    $wp = $this->createMock(WPFunctions::class);
    $activator = $this->createActivator($settings, $cronActionScheduler, $daemonActionSchedulerRunner, $wp);

    $cronActionScheduler->expects($this->once())
      ->method('isInitialized')
      ->willReturn(false);
    $cronActionScheduler->expects($this->once())
      ->method('runAfterInitialized')
      ->with([$activator, 'refreshCronActions']);
    $cronActionScheduler->expects($this->never())
      ->method('unscheduleAllCronActions');
    $cronActionScheduler->expects($this->never())
      ->method('hasScheduledAction');
    $daemonActionSchedulerRunner->expects($this->never())
      ->method('clearDeactivationFlag');

    $activator->refreshCronActions();
  }

  public function testRefreshCronActionsRunsImmediatelyWhenActionSchedulerIsInitialized(): void {
    $settings = $this->createMock(SettingsController::class);
    $settings->method('get')
      ->with(CronTrigger::SETTING_NAME . '.method')
      ->willReturn(CronTrigger::METHOD_ACTION_SCHEDULER);

    $cronActionScheduler = $this->createMock(CronActionScheduler::class);
    $daemonActionSchedulerRunner = $this->createMock(DaemonActionSchedulerRunner::class);
    $wp = $this->createMock(WPFunctions::class);
    $activator = $this->createActivator($settings, $cronActionScheduler, $daemonActionSchedulerRunner, $wp);

    $cronActionScheduler->expects($this->once())
      ->method('isInitialized')
      ->willReturn(true);
    $cronActionScheduler->expects($this->once())
      ->method('unscheduleAllCronActions');
    $daemonActionSchedulerRunner->expects($this->once())
      ->method('clearDeactivationFlag');
    $cronActionScheduler->expects($this->once())
      ->method('hasScheduledAction')
      ->with(DaemonTrigger::NAME)
      ->willReturn(false);
    $wp->expects($this->once())
      ->method('currentTime')
      ->with('timestamp', true)
      ->willReturn(123456);
    $cronActionScheduler->expects($this->once())
      ->method('scheduleRecurringAction')
      ->with(123456, DaemonTrigger::TRIGGER_RUN_INTERVAL, DaemonTrigger::NAME);
    $cronActionScheduler->expects($this->never())
      ->method('runAfterInitialized');

    $activator->refreshCronActions();
  }

  public function testRefreshCronActionsOnlyClearsFlagWhenMethodIsNotActionScheduler(): void {
    $settings = $this->createMock(SettingsController::class);
    $settings->expects($this->once())
      ->method('get')
      ->with(CronTrigger::SETTING_NAME . '.method')
      ->willReturn(CronTrigger::METHOD_WORDPRESS);

    $cronActionScheduler = $this->createMock(CronActionScheduler::class);
    $daemonActionSchedulerRunner = $this->createMock(DaemonActionSchedulerRunner::class);
    $wp = $this->createMock(WPFunctions::class);
    $activator = $this->createActivator($settings, $cronActionScheduler, $daemonActionSchedulerRunner, $wp);

    $daemonActionSchedulerRunner->expects($this->once())
      ->method('clearDeactivationFlag');
    $cronActionScheduler->expects($this->never())
      ->method('isInitialized');
    $cronActionScheduler->expects($this->never())
      ->method('unscheduleAllCronActions');
    $cronActionScheduler->expects($this->never())
      ->method('runAfterInitialized');

    $activator->refreshCronActions();
  }

  private function createActivator(
    SettingsController $settings,
    CronActionScheduler $cronActionScheduler,
    DaemonActionSchedulerRunner $daemonActionSchedulerRunner,
    WPFunctions $wp
  ): Activator {
    return new Activator(
      $this->createMock(Connection::class),
      $settings,
      $this->createMock(Populator::class),
      $wp,
      $this->createMock(Migrator::class),
      $cronActionScheduler,
      $daemonActionSchedulerRunner
    );
  }
}
