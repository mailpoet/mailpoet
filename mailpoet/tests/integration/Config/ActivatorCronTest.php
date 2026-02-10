<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\Cron\ActionScheduler\Actions\DaemonTrigger;
use MailPoet\Cron\ActionScheduler\ActionSchedulerTestHelper;
use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\DaemonActionSchedulerRunner;
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
    update_option(DaemonActionSchedulerRunner::DEACTIVATION_FLAG_OPTION, true);
    verify($this->daemonActionSchedulerRunner->isDeactivating())->true();

    $this->activator->activate();

    verify($this->daemonActionSchedulerRunner->isDeactivating())->false();
  }

  private function cleanup(): void {
    global $wpdb;
    $actionsTable = $wpdb->prefix . 'actionscheduler_actions';
    $wpdb->query($wpdb->prepare('TRUNCATE %i', $actionsTable));
  }
}
