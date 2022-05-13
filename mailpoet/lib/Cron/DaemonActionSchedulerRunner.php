<?php declare(strict_types=1);

namespace MailPoet\Cron;

use MailPoet\Cron\Triggers\WordPress;
use MailPoet\WP\Functions as WPFunctions;

class DaemonActionSchedulerRunner {
  const DAEMON_RUN_SCHEDULER_ACTION = 'mailpoet/cron/daemon-run';
  const DAEMON_TRIGGER_SCHEDULER_ACTION = 'mailpoet/cron/daemon-trigger';
  const RUN_ACTION_SCHEDULER = 'mailpoet-cron-action-scheduler-run';

  /** @var Daemon */
  private $daemon;

  /** @var WordPress */
  private $wordpressTrigger;

  /** @var CronHelper */
  private $cronHelper;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    Daemon $daemon,
    CronHelper $cronHelper,
    WordPress $wordpressTrigger,
    WPFunctions $wp
  ) {
    $this->cronHelper = $cronHelper;
    $this->daemon = $daemon;
    $this->wordpressTrigger = $wordpressTrigger;
    $this->wp = $wp;
  }

  public function init() {
    $this->wp->addAction(self::DAEMON_RUN_SCHEDULER_ACTION, [$this, 'run']);
    $this->wp->addAction(self::DAEMON_TRIGGER_SCHEDULER_ACTION, [$this, 'trigger']);
    $this->wp->addAction('wp_ajax_nopriv_' . self::RUN_ACTION_SCHEDULER, [$this, 'runActionScheduler'], 0);
    $this->wp->addAction('action_scheduler_after_process_queue', [$this, 'afterProcess']);
    if (!as_has_scheduled_action(self::DAEMON_TRIGGER_SCHEDULER_ACTION)) {
      as_schedule_recurring_action($this->wp->currentTime('timestamp'), 20, self::DAEMON_TRIGGER_SCHEDULER_ACTION);
    }
  }

  /**
   * In regular intervals checks if there are scheduled tasks to execute.
   * In case there are tasks it spawns a recurring action.
   * @return void
   */
  public function trigger() {
    $hasJobsToDo = $this->wordpressTrigger->checkExecutionRequirements();
    if (!$hasJobsToDo) {
      as_unschedule_action(self::DAEMON_RUN_SCHEDULER_ACTION);
      return;
    }
    if (as_has_scheduled_action(self::DAEMON_RUN_SCHEDULER_ACTION)) {
      return;
    }
    // Start recurring action with minimal interval to ensure continuous execution of the daemon
    as_schedule_recurring_action($this->wp->currentTime('timestamp') - 1, 1, self::DAEMON_RUN_SCHEDULER_ACTION);
    $this->triggerRemoteExecutor();
  }

  /**
   * Run daemon that processes scheduled tasks for limited time (default 20 seconds)
   */
  public function run(): void {
    $this->daemon->run($this->cronHelper->createDaemon($this->cronHelper->createToken()));
  }

  /**
   * This method is triggered by an ajax request.
   * It creates ActionScheduler runner and process a batch actions that are ready to be processed
   * @return void
   */
  public function runActionScheduler() {
    $this->wp->addFilter( 'action_scheduler_queue_runner_concurrent_batches', [$this, 'ensureConcurrency']);
    \ActionScheduler_QueueRunner::instance()->run();
    wp_die();
  }

  /**
   * When triggering Action Runner we need to make sure we are able to trigger new runner from a runner
   * @return int
   */
  public function ensureConcurrency(int $concurrency): int {
    return ($concurrency) < 2 ? 2 : $concurrency;
  }

  /**
   * After Action Scheduler finishes queue always check there is more work to do and in case there is trigger additional runner.
   */
  public function afterProcess() {
    if ($this->wordpressTrigger->checkExecutionRequirements()) {
      sleep(2); // Add short sleep to ensure next action ready to be processed since minimal schedule interval is 1 second
      $this->triggerRemoteExecutor();
    } else {
      as_unschedule_action(self::DAEMON_RUN_SCHEDULER_ACTION);
    }
  }

  /**
   * Spawns addition Action Scheduler runner
   * @see https://actionscheduler.org/perf/#increasing-initialisation-rate-of-runners
   * @return void
   */
  private function triggerRemoteExecutor() {
    $this->wp->addFilter('https_local_ssl_verify', '__return_false', 100);
    $this->wp->wpRemotePost( $this->wp->adminUrl( 'admin-ajax.php' ), [
      'method' => 'POST',
      'timeout' => 5,
      'redirection' => 5,
      'httpversion' => '1.0',
      'blocking' => false,
      'headers' => [],
      'body' => [
        'action' => self::RUN_ACTION_SCHEDULER,
      ],
      'cookies' => [],
    ]);
  }
}
