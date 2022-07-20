<?php declare(strict_types=1);

namespace MailPoet\Cron;

use MailPoet\Cron\ActionScheduler\Actions\DaemonTrigger;
use MailPoet\Cron\ActionScheduler\ActionSchedulerTestHelper;
use MailPoet\Entities\ScheduledTaskEntity;

require_once __DIR__ . '/ActionScheduler/ActionSchedulerTestHelper.php';

class DaemonActionSchedulerRunnerTest extends \MailPoetTest {

  /** @var DaemonActionSchedulerRunner */
  private $actionSchedulerRunner;

  /** @var ActionSchedulerTestHelper */
  private $actionSchedulerHelper;

  public function _before(): void {
    $this->actionSchedulerRunner = $this->diContainer->get(DaemonActionSchedulerRunner::class);
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

  private function cleanup(): void {
    global $wpdb;
    $actionsTable = $wpdb->prefix . 'actionscheduler_actions';
    $wpdb->query('TRUNCATE ' . $actionsTable);
    $this->truncateEntity(ScheduledTaskEntity::class);
  }
}
