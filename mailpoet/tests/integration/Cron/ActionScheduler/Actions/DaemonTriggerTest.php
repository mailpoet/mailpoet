<?php declare(strict_types = 1);

namespace MailPoet\Cron\ActionScheduler\Actions;

use MailPoet\Cron\ActionScheduler\ActionScheduler;
use MailPoet\Cron\ActionScheduler\ActionSchedulerTestHelper;
use MailPoet\Cron\ActionScheduler\RemoteExecutorHandler;
use MailPoet\Cron\CronTrigger;
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
    $this->scheduledTaskFactory = new ScheduledTask();
    $this->scheduledTaskFactory->withDefaultTasks();
    $this->actionSchedulerHelper = new ActionSchedulerTestHelper();
  }

  public function testItSchedulesTriggerActionOnInit(): void {
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(0);
    $this->daemonTrigger->init();
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(1);
    $action = reset($actions);
    $this->assertInstanceOf(\ActionScheduler_Action::class, $action);
    expect($action->get_hook())->equals(DaemonTrigger::NAME);
  }

  public function testTriggerDoesNotTriggerAnythingIfThereAreNoJobs(): void {
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(0);
    $this->daemonTrigger->process();
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(0);
  }

  public function testTriggerUnschedulesRunJobIfThereIsNoMoreWork(): void {
    $actionScheduler = $this->diContainer->get(ActionScheduler::class);
    $actionScheduler->scheduleRecurringAction(time() + 60, 1, DaemonRun::NAME);
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(1);
    $this->daemonTrigger->process();
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(0);
  }

  public function testTriggerTriggerRunnerActionWhenThereIsJob(): void {
    $this->diContainer->get(SettingsController::class)->set('cron_trigger.method', CronTrigger::METHOD_ACTION_SCHEDULER);
    $this->createDueScheduledTask();
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(0);
    $remoteExecutorHandlerMock = $this->createMock(RemoteExecutorHandler::class);
    $remoteExecutorHandlerMock->expects($this->once())
      ->method('triggerExecutor');
    $daemonTrigger = $this->getServiceWithOverrides(DaemonTrigger::class, [
      'remoteExecutorHandler' => $remoteExecutorHandlerMock,
    ]);
    $daemonTrigger->process();
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(1);
    $action = reset($actions);
    $this->assertInstanceOf(\ActionScheduler_Action::class, $action);
    expect($action->get_hook())->equals(DaemonRun::NAME);
    $this->cleanup();
  }

  private function createDueScheduledTask(): void {
    $date = Carbon::now()->subSecond();
    $this->scheduledTaskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, $date);
  }

  private function cleanup(): void {
    global $wpdb;
    $actionsTable = $wpdb->prefix . 'actionscheduler_actions';
    $wpdb->query('TRUNCATE ' . $actionsTable);
    $claimsTable = $wpdb->prefix . 'actionscheduler_claims';
    $wpdb->query('TRUNCATE ' . $claimsTable);
  }
}
