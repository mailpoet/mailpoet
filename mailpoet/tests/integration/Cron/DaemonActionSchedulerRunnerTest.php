<?php declare(strict_types=1);

namespace MailPoet\Cron;

use MailPoet\Cron\ActionScheduler\ActionScheduler;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\WP\Functions;
use MailPoetVendor\Carbon\Carbon;

class DaemonActionSchedulerRunnerTest extends \MailPoetTest {

  /** @var DaemonActionSchedulerRunner */
  private $actionSchedulerRunner;

  /** @var ScheduledTask */
  private $scheduledTaskFactory;

  public function _before() {
    $this->actionSchedulerRunner = $this->diContainer->get(DaemonActionSchedulerRunner::class);
    $this->cleanup();
    $this->scheduledTaskFactory = new ScheduledTask();
    $this->scheduledTaskFactory->withDefaultTasks();
  }

  public function testItSchedulesTriggerActionOnInit() {
    $actions = $this->getMailPoetScheduledActions();
    expect($actions)->count(0);
    $this->actionSchedulerRunner->init();
    $actions = $this->getMailPoetScheduledActions();
    expect($actions)->count(1);
    $action = reset($actions);
    $this->assertInstanceOf(\ActionScheduler_Action::class, $action);
    expect($action->get_hook())->equals(DaemonActionSchedulerRunner::DAEMON_TRIGGER_SCHEDULER_ACTION);
  }

  public function testItDeactivateAllTasks() {
    $this->actionSchedulerRunner->init();
    $actions = $this->getMailPoetScheduledActions();
    expect($actions)->count(1);
    $this->actionSchedulerRunner->deactivate();
    $actions = $this->getMailPoetScheduledActions();
    expect($actions)->count(0);
  }

  public function testTriggerDoesNotTriggerAnythingIfThereAreNoJobs() {
    $actions = $this->getMailPoetScheduledActions();
    expect($actions)->count(0);
    $this->actionSchedulerRunner->trigger();
    $actions = $this->getMailPoetScheduledActions();
    expect($actions)->count(0);
  }

  public function testTriggerUnschedulesRunJobIfThereIsNoMoreWork() {
    $actionScheduler = $this->diContainer->get(ActionScheduler::class);
    $actionScheduler->scheduleRecurringAction(time() + 60, 1, DaemonActionSchedulerRunner::DAEMON_RUN_SCHEDULER_ACTION);
    $actions = $this->getMailPoetScheduledActions();
    expect($actions)->count(1);
    $this->actionSchedulerRunner->trigger();
    $actions = $this->getMailPoetScheduledActions();
    expect($actions)->count(0);
  }

  public function testTriggerTriggerRunnerActionWhenThereIsJob() {
    $this->diContainer->get(SettingsController::class)->set('cron_trigger.method', CronTrigger::METHOD_ACTION_SCHEDULER);
    $this->createDueScheduledTask();
    $actions = $this->getMailPoetScheduledActions();
    expect($actions)->count(0);
    $this->actionSchedulerRunner->trigger();
    $actions = $this->getMailPoetScheduledActions();
    expect($actions)->count(2);
    $action = reset($actions);
    $this->assertInstanceOf(\ActionScheduler_Action::class, $action);
    expect($action->get_hook())->equals(DaemonActionSchedulerRunner::DAEMON_RUN_SCHEDULER_ACTION);
    $this->cleanup();
  }

  public function testRunnerCanProcessActions() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('cron_trigger.method', CronTrigger::METHOD_ACTION_SCHEDULER);
    // We need configure sender so that Daemon::run doesn't fail due incomplete configuration for Mailer.
    $settings->set('sender', [
      'name' => 'John',
      'address' => 'john@example.com',
    ]);
    // Activate filter for watching execution limit.
    // This normally happens in DaemonActionSchedulerRunner::init but it can't be called in tests since it cause some background requests and made test flaky
    $wp = $this->diContainer->get(Functions::class);
    $wp->addFilter('action_scheduler_maximum_execution_time_likely_to_be_exceeded', [$this->actionSchedulerRunner, 'storeRemainingExecutionLimit'], 10, 5);
    expect($this->actionSchedulerRunner->getDaemonExecutionLimit())->equals(20); // Verify initial execution limit

    $actionScheduler = $this->diContainer->get(ActionScheduler::class);
    $actionScheduler->scheduleRecurringAction(time() - 1, 100, DaemonActionSchedulerRunner::DAEMON_RUN_SCHEDULER_ACTION);
    $actions = $this->getMailPoetScheduledActions();
    expect($actions)->count(1);
    $doneActions = $this->getMailPoetCompleteActions();
    expect($doneActions)->count(0);

    // We can't call $this->actionSchedulerRunner->runActionScheduler directly because it ends up with wp_die();
    // We must also instantiate fresh runner, because the global instance may have exhausted execution time, because it is created
    // at the start of all tests
    $runner = new \ActionScheduler_QueueRunner();
    $runner->run();

    $doneActions = $this->getMailPoetCompleteActions();
    expect($doneActions)->count(1);
    $actions = $this->getMailPoetScheduledActions();
    expect($actions)->count(1);

    // Verify execution limit after run. floor(30 - some time taken by previous action) - 10s (safety execution timout margin)
    expect($this->actionSchedulerRunner->getDaemonExecutionLimit())->greaterThan(0);
    expect($this->actionSchedulerRunner->getDaemonExecutionLimit())->lessThan(20);
  }

  private function getMailPoetScheduledActions() {
    $actions = as_get_scheduled_actions([
      'group' => ActionScheduler::GROUP_ID,
      'status' => [\ActionScheduler_Store::STATUS_PENDING, \ActionScheduler_Store::STATUS_RUNNING],
    ]);
    return $actions;
  }

  private function getMailPoetCompleteActions() {
    $actions = as_get_scheduled_actions([
      'group' => ActionScheduler::GROUP_ID,
      'status' => [\ActionScheduler_Store::STATUS_COMPLETE],
    ]);
    return $actions;
  }

  private function createDueScheduledTask() {
    $date = Carbon::now()->subSecond();
    $this->scheduledTaskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, $date);
  }

  private function cleanup() {
    global $wpdb;
    $actionsTable = $wpdb->prefix . 'actionscheduler_actions';
    $wpdb->query('TRUNCATE ' . $actionsTable);
    $this->truncateEntity(ScheduledTaskEntity::class);
  }
}
