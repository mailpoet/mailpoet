<?php declare(strict_types = 1);

namespace MailPoet\Cron\ActionScheduler\Actions;

use MailPoet\Cron\ActionScheduler\ActionScheduler;
use MailPoet\Cron\ActionScheduler\RemoteExecutorHandler;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Daemon;
use MailPoet\Cron\Triggers\WordPress;
use MailPoet\WP\Functions as WPFunctions;

class DaemonRun {
  const NAME = 'mailpoet/cron/daemon-run';
  const EXECUTION_LIMIT_MARGIN = 10; // 10 seconds

  /** @var WPFunctions */
  private $wp;

  /** @var Daemon */
  private $daemon;

  /** @var WordPress */
  private $wordpressTrigger;

  /** @var CronHelper */
  private $cronHelper;

  /** @var RemoteExecutorHandler */
  private $remoteExecutorHandler;

  /** @var ActionScheduler */
  private $actionScheduler;

  /**
   * Default 20 seconds
   * @var float
   */
  private $remainingExecutionLimit = 20;

  public function __construct(
    WPFunctions $wp,
    Daemon $daemon,
    WordPress $wordpressTrigger,
    CronHelper $cronHelper,
    RemoteExecutorHandler $remoteExecutorHandler,
    ActionScheduler $actionScheduler
  ) {
    $this->wp = $wp;
    $this->daemon = $daemon;
    $this->wordpressTrigger = $wordpressTrigger;
    $this->cronHelper = $cronHelper;
    $this->remoteExecutorHandler = $remoteExecutorHandler;
    $this->actionScheduler = $actionScheduler;
  }

  public function init(): void {
    $this->wp->addAction(self::NAME, [$this, 'process']);
    $this->wp->addFilter('action_scheduler_maximum_execution_time_likely_to_be_exceeded', [$this, 'storeRemainingExecutionLimit'], 10, 5);
  }

  /**
   * Run daemon that processes scheduled tasks for limited time
   */
  public function process(): void {
    $this->wp->addAction('action_scheduler_after_process_queue', [$this, 'afterProcess']);
    $this->wp->addAction('mailpoet_cron_get_execution_limit', [$this, 'getDaemonExecutionLimit']);
    $this->daemon->run($this->cronHelper->createDaemon($this->cronHelper->createToken()));
  }

  /**
   * Callback for setting the remaining execution time for the cron daemon (MailPoet\Cron\Daemon)
   */
  public function getDaemonExecutionLimit(): float {
    return $this->remainingExecutionLimit;
  }

  /**
   * After Action Scheduler finishes its work we need to check if there is more work and in case there is we trigger additional runner.
   */
  public function afterProcess(): void {
    if ($this->wordpressTrigger->checkExecutionRequirements()) {
      $this->actionScheduler->scheduleImmediateSingleAction(self::NAME);
      // The automatic rescheduling schedules the next recurring action to run after 1 second.
      // So we need to wait before we trigger new remote executor to avoid skipping the action
      sleep(2);
      $this->remoteExecutorHandler->triggerExecutor();
    }
  }

  /**
   * This method is hooked into action_scheduler_maximum_execution_time_likely_to_be_exceeded
   * It checks how much execution time is left for the daemon to run
   */
  public function storeRemainingExecutionLimit($likelyExceeded, $runner, $processedActions, $executionTime, $maxExecutionTime): bool {
    $newLimit = ($maxExecutionTime - $executionTime) - self::EXECUTION_LIMIT_MARGIN;
    $this->remainingExecutionLimit = max($newLimit, 0);
    return (bool)$likelyExceeded;
  }
}
