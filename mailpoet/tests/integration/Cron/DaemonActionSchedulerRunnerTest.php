<?php declare(strict_types = 1);

namespace MailPoet\Cron;

use MailPoet\Cron\ActionScheduler\Actions\DaemonRun;
use MailPoet\Cron\ActionScheduler\Actions\DaemonTrigger;
use MailPoet\Cron\ActionScheduler\ActionScheduler;
use MailPoet\Cron\ActionScheduler\ActionSchedulerTestHelper;

require_once __DIR__ . '/ActionScheduler/ActionSchedulerTestHelper.php';

class DaemonActionSchedulerRunnerTest extends \MailPoetTest {

  /** @var DaemonActionSchedulerRunner */
  private $actionSchedulerRunner;

  /** @var ActionSchedulerTestHelper */
  private $actionSchedulerHelper;

  /** @var ActionScheduler */
  private $actionScheduler;

  public function _before(): void {
    $this->actionSchedulerRunner = $this->diContainer->get(DaemonActionSchedulerRunner::class);
    $this->actionScheduler = $this->diContainer->get(ActionScheduler::class);
    $this->cleanup();
    $this->actionSchedulerHelper = new ActionSchedulerTestHelper();
  }

  public function testItSchedulesTriggerActionOnInit(): void {
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(0);
    $this->actionSchedulerRunner->init();
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(1);
    $action = reset($actions);
    $this->assertInstanceOf(\ActionScheduler_Action::class, $action);
    expect($action->get_hook())->equals(DaemonTrigger::NAME);
  }

  public function testItDeactivateAllTasks(): void {
    $this->actionSchedulerRunner->init();
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(1);
    $this->actionSchedulerRunner->deactivate();
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(0);
  }

  public function testItDeactivatesAllTasksOnTrigger(): void {
    $this->actionScheduler->scheduleRecurringAction(time() - 1, 100, DaemonTrigger::NAME);
    $this->actionScheduler->scheduleImmediateSingleAction(DaemonRun::NAME);
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(2);
    $this->actionSchedulerRunner->init(false);

    $runner = new \ActionScheduler_QueueRunner();
    $runner->run();

    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(0);
  }

  private function cleanup(): void {
    global $wpdb;
    $actionsTable = $wpdb->prefix . 'actionscheduler_actions';
    $wpdb->query('TRUNCATE ' . $actionsTable);
  }
}
