<?php declare(strict_types=1);

namespace MailPoet\Cron;

use MailPoet\Cron\ActionScheduler\ActionScheduler;
use MailPoet\Cron\ActionScheduler\RemoteExecutorHandler;
use MailPoet\Cron\Triggers\WordPress;
use MailPoet\WP\Functions as WPFunctions;

class DaemonActionSchedulerRunner {
  const DAEMON_RUN_SCHEDULER_ACTION = 'mailpoet/cron/daemon-run';
  const DAEMON_TRIGGER_SCHEDULER_ACTION = 'mailpoet/cron/daemon-trigger';

  const EXECUTION_LIMIT_MARGIN = 10; // 10 seconds

  /** @var Daemon */
  private $daemon;

  /** @var WordPress */
  private $wordpressTrigger;

  /** @var CronHelper */
  private $cronHelper;

  /** @var WPFunctions */
  private $wp;

  /** @var ActionScheduler */
  private $actionScheduler;

  /** @var RemoteExecutorHandler */
  private $remoteExecutorHandler;

  /**
   * The inital value is set based on default cron execution limit battle tested in MailPoet custom cron runner (an older version of the background processing).
   * The default limit in PHP is 30s so it leaves 10 execution margin.
   * @var int
   */
  private $remainingExecutionLimit = 20;

  public function __construct(
    Daemon $daemon,
    CronHelper $cronHelper,
    WordPress $wordpressTrigger,
    WPFunctions $wp,
    ActionScheduler $actionScheduler,
    RemoteExecutorHandler $remoteExecutorHandler
  ) {
    $this->cronHelper = $cronHelper;
    $this->daemon = $daemon;
    $this->wordpressTrigger = $wordpressTrigger;
    $this->wp = $wp;
    $this->actionScheduler = $actionScheduler;
    $this->remoteExecutorHandler = $remoteExecutorHandler;
  }

  public function init(): void {
    $this->wp->addAction(self::DAEMON_RUN_SCHEDULER_ACTION, [$this, 'run']);
    $this->wp->addAction(self::DAEMON_TRIGGER_SCHEDULER_ACTION, [$this, 'trigger']);
    $this->remoteExecutorHandler->init();
    $this->wp->addFilter('action_scheduler_maximum_execution_time_likely_to_be_exceeded', [$this, 'storeRemainingExecutionLimit'], 10, 5);
    if (!$this->actionScheduler->hasScheduledAction(self::DAEMON_TRIGGER_SCHEDULER_ACTION)) {
      $this->actionScheduler->scheduleRecurringAction($this->wp->currentTime('timestamp'), 20, self::DAEMON_TRIGGER_SCHEDULER_ACTION);
    }
  }

  public function deactivate(): void {
    $this->actionScheduler->unscheduleAction(self::DAEMON_TRIGGER_SCHEDULER_ACTION);
    $this->actionScheduler->unscheduleAction(self::DAEMON_RUN_SCHEDULER_ACTION);
  }

  /**
   * In regular intervals checks if there are scheduled tasks to execute.
   * In case there are tasks it spawns a recurring action.
   */
  public function trigger(): void {
    $hasJobsToDo = $this->wordpressTrigger->checkExecutionRequirements();
    if (!$hasJobsToDo) {
      $this->actionScheduler->unscheduleAction(self::DAEMON_RUN_SCHEDULER_ACTION);
      return;
    }
    if ($this->actionScheduler->hasScheduledAction(self::DAEMON_RUN_SCHEDULER_ACTION)) {
      return;
    }
    // Start recurring action with minimal interval to ensure continuous execution of the daemon
    $this->actionScheduler->scheduleRecurringAction($this->wp->currentTime('timestamp') - 1, 1, self::DAEMON_RUN_SCHEDULER_ACTION);
    $this->remoteExecutorHandler->triggerExecutor();
  }

  /**
   * Run daemon that processes scheduled tasks for limited time (default 20 seconds)
   */
  public function run(): void {
    $this->wp->addAction('action_scheduler_after_process_queue', [$this, 'afterProcess']);
    $this->wp->addAction('mailpoet_cron_get_execution_limit', [$this, 'getDaemonExecutionLimit']);
    $this->daemon->run($this->cronHelper->createDaemon($this->cronHelper->createToken()));
  }

  /**
   * Callback for a hook for adjusting the execution for the cron daemon
   */
  public function getDaemonExecutionLimit(): int {
    return $this->remainingExecutionLimit;
  }

  /**
   * After Action Scheduler finishes queue always check there is more work to do and in case there is trigger additional runner.
   */
  public function afterProcess(): void {
    if ($this->wordpressTrigger->checkExecutionRequirements()) {
      sleep(2); // Add short sleep to ensure next action ready to be processed since minimal schedule interval is 1 second
      $this->remoteExecutorHandler->triggerExecutor();
    } else {
      $this->actionScheduler->unscheduleAction(self::DAEMON_RUN_SCHEDULER_ACTION);
    }
  }

  /**
   * This method is hooked into action_scheduler_maximum_execution_time_likely_to_be_exceeded
   * and used to listen on how many execution time is needed.
   * The execution limit is then used for the daemon run
   */
  public function storeRemainingExecutionLimit($likelyExceeded, $runner, $processedActions, $executionTime, $maxExecutionTime): bool {
    $newLimit = floor(($maxExecutionTime - $executionTime) - self::EXECUTION_LIMIT_MARGIN);
    $this->remainingExecutionLimit = intval(max($newLimit, 0));
    return (bool)$likelyExceeded;
  }
}
