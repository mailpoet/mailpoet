<?php declare(strict_types=1);

namespace MailPoet\Cron\ActionScheduler\Actions;

use MailPoet\Cron\ActionScheduler\ActionScheduler;
use MailPoet\Cron\ActionScheduler\ActionSchedulerTestHelper;
use MailPoet\Cron\CronTrigger;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\ScheduledTask;

require_once __DIR__ . '/../ActionSchedulerTestHelper.php';

class DaemonRunTest extends \MailPoetTest {

  /** @var DaemonRun */
  private $daemonRun;

  /** @var ActionScheduler */
  private $actionScheduler;

  /** @var ActionSchedulerTestHelper */
  private $actionSchedulerHelper;

  public function _before(): void {
    $this->daemonRun = $this->diContainer->get(DaemonRun::class);
    $this->actionScheduler = $this->diContainer->get(ActionScheduler::class);
    $this->cleanup();
    (new ScheduledTask())->withDefaultTasks();
    $this->actionSchedulerHelper = new ActionSchedulerTestHelper();
  }

  public function testCanProcessActions(): void {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('cron_trigger.method', CronTrigger::METHOD_ACTION_SCHEDULER);
    // We need configure sender so that Daemon::run doesn't fail due incomplete configuration for Mailer.
    $settings->set('sender', [
      'name' => 'John',
      'address' => 'john@example.com',
    ]);
    $this->daemonRun->init();
    expect($this->daemonRun->getDaemonExecutionLimit())->equals(20); // Verify initial execution limit

    $this->actionScheduler->scheduleRecurringAction(time() - 1, 100, DaemonRun::NAME);
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(1);
    $doneActions = $this->actionSchedulerHelper->getMailPoetCompleteActions();
    expect($doneActions)->count(0);

    // We can't call $this->daemonRun->process directly because it ends up with wp_die();
    // We must also instantiate fresh runner, because the global instance may have exhausted execution time, because it is created
    // at the start of all tests
    $runner = new \ActionScheduler_QueueRunner();
    $runner->run();

    $doneActions = $this->actionSchedulerHelper->getMailPoetCompleteActions();
    expect($doneActions)->count(1);
    $actions = $this->actionSchedulerHelper->getMailPoetScheduledActions();
    expect($actions)->count(0);

    // Verify execution limit after run. floor(30 - some time taken by previous action) - 10s (safety execution timout margin)
    expect($this->daemonRun->getDaemonExecutionLimit())->greaterThan(0);
    expect($this->daemonRun->getDaemonExecutionLimit())->lessThan(20);
  }

  private function cleanup(): void {
    global $wpdb;
    $actionsTable = $wpdb->prefix . 'actionscheduler_actions';
    $wpdb->query('TRUNCATE ' . $actionsTable);
    $claimsTable = $wpdb->prefix . 'actionscheduler_claims';
    $wpdb->query('TRUNCATE ' . $claimsTable);
    $this->truncateEntity(ScheduledTaskEntity::class);
  }
}
