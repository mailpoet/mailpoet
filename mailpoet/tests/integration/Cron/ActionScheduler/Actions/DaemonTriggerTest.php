<?php declare(strict_types = 1);

namespace MailPoet\Cron\ActionScheduler\Actions;

use MailPoet\Cron\ActionScheduler\ActionScheduler;
use MailPoet\Cron\ActionScheduler\ActionSchedulerTestHelper;
use MailPoet\Cron\ActionScheduler\RemoteExecutorHandler;
use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\DaemonActionSchedulerRunner;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoetVendor\Carbon\Carbon;

require_once __DIR__ . '/../ActionSchedulerTestHelper.php';

class DaemonTriggerTest extends \MailPoetTest {

  /** @var DaemonTrigger */
  private $daemonTrigger;

  /** @var ScheduledTask */
  private $scheduledTaskFactory;

  /** @var ActionSchedulerTestHelper */
  private $actionSchedulerHelper;

  public function _before(): void {
    $this->daemonTrigger = $this->diContainer->get(DaemonTrigger::class);
    $this->cleanup();
    // Clear deactivation flag to ensure clean state for each test
    $this->diContainer->get(DaemonActionSchedulerRunner::class)->clearDeactivationFlag();
    $this->scheduledTaskFactory = new ScheduledTask();
    $this->scheduledTaskFactory->withDefaultTasks();
    $this->actionSchedulerHelper = new ActionSchedulerTestHelper();
  }

  public function testItSchedulesTriggerActionOnInit(): void {
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(0);
    $this->daemonTrigger->init();
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(1);
    $action = reset($actions);
    $this->assertInstanceOf(\ActionScheduler_Action::class, $action);
    verify($action->get_hook())->equals(DaemonTrigger::NAME);
  }

  public function testItDoesNotScheduleTriggerActionWhenDeactivating(): void {
    $actionSchedulerRunner = $this->diContainer->get(DaemonActionSchedulerRunner::class);

    // Set the deactivation flag (simulating mid-deactivation)
    update_option(DaemonActionSchedulerRunner::DEACTIVATION_FLAG_OPTION, time());

    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(0);

    $this->daemonTrigger->init();

    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(0); // Should still be 0 because deactivation flag is set

    // Cleanup
    $actionSchedulerRunner->clearDeactivationFlag();
  }

  public function testItSchedulesTriggerActionWhenDeactivationFlagIsStale(): void {
    update_option(DaemonActionSchedulerRunner::DEACTIVATION_FLAG_OPTION, time() - DaemonActionSchedulerRunner::DEACTIVATION_FLAG_TTL - 1);

    $this->daemonTrigger->init();

    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(1);
    $action = reset($actions);
    $this->assertInstanceOf(\ActionScheduler_Action::class, $action);
    verify($action->get_hook())->equals(DaemonTrigger::NAME);
  }

  public function testItSchedulesTriggerActionWhenLegacyDeactivationFlagIsSet(): void {
    // Boolean flag values written by older versions could get stuck and are treated as stale
    update_option(DaemonActionSchedulerRunner::DEACTIVATION_FLAG_OPTION, true);

    $this->daemonTrigger->init();

    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(1);
  }

  public function testItDoesNotDeleteConcurrentlyWrittenFlagWhenScheduling(): void {
    // A stale flag must not block rescheduling, but init() must not delete the option either —
    // a concurrent deactivate() may have just replaced it with a fresh value
    $staleValue = time() - DaemonActionSchedulerRunner::DEACTIVATION_FLAG_TTL - 1;
    update_option(DaemonActionSchedulerRunner::DEACTIVATION_FLAG_OPTION, $staleValue);

    $this->daemonTrigger->init();

    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(1);
    verify(get_option(DaemonActionSchedulerRunner::DEACTIVATION_FLAG_OPTION))->equals($staleValue);
  }

  public function testTriggerDoesNotTriggerAnythingIfThereAreNoJobs(): void {
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(0);
    $this->daemonTrigger->process();
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(0);
  }

  public function testTriggerUnschedulesRunJobIfThereIsNoMoreWork(): void {
    $actionScheduler = $this->diContainer->get(ActionScheduler::class);
    $actionScheduler->scheduleRecurringAction(time() + 60, 1, DaemonRun::NAME);
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(1);
    $this->daemonTrigger->process();
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(0);
  }

  public function testTriggerTriggerRunnerActionWhenThereIsJob(): void {
    $this->diContainer->get(SettingsController::class)->set('cron_trigger.method', CronTrigger::METHOD_ACTION_SCHEDULER);
    $this->createDueScheduledTask();
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(0);
    $remoteExecutorHandlerMock = $this->createMock(RemoteExecutorHandler::class);
    $remoteExecutorHandlerMock->expects($this->once())
      ->method('triggerExecutor');
    $daemonTrigger = $this->getServiceWithOverrides(DaemonTrigger::class, [
      'remoteExecutorHandler' => $remoteExecutorHandlerMock,
    ]);
    $daemonTrigger->process();
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    verify($actions)->arrayCount(1);
    $action = reset($actions);
    $this->assertInstanceOf(\ActionScheduler_Action::class, $action);
    verify($action->get_hook())->equals(DaemonRun::NAME);
    $this->cleanup();
  }

  private function createDueScheduledTask(): void {
    $date = Carbon::now()->subSecond();
    $this->scheduledTaskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, $date);
  }

  private function cleanup(): void {
    global $wpdb;
    $wpdb->query($wpdb->prepare('TRUNCATE %i', $wpdb->prefix . 'actionscheduler_actions'));
    $wpdb->query($wpdb->prepare('TRUNCATE %i', $wpdb->prefix . 'actionscheduler_claims'));
  }
}
