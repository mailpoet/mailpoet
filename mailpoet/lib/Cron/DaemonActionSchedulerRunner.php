<?php declare(strict_types=1);

namespace MailPoet\Cron;

use MailPoet\Cron\ActionScheduler\Actions\DaemonRun;
use MailPoet\Cron\ActionScheduler\Actions\DaemonTrigger;
use MailPoet\Cron\ActionScheduler\ActionScheduler;
use MailPoet\Cron\ActionScheduler\RemoteExecutorHandler;
use MailPoet\WP\Functions as WPFunctions;

class DaemonActionSchedulerRunner {
  /** @var ActionScheduler */
  private $actionScheduler;

  /** @var RemoteExecutorHandler */
  private $remoteExecutorHandler;

  /** @var DaemonTrigger */
  private $daemonTriggerAction;

  /** @var DaemonRun */
  private $daemonRunAction;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    ActionScheduler $actionScheduler,
    RemoteExecutorHandler $remoteExecutorHandler,
    DaemonTrigger $daemonTriggerAction,
    DaemonRun $daemonRunAction,
    WPFunctions $wp
  ) {
    $this->actionScheduler = $actionScheduler;
    $this->remoteExecutorHandler = $remoteExecutorHandler;
    $this->daemonTriggerAction = $daemonTriggerAction;
    $this->daemonRunAction = $daemonRunAction;
    $this->wp = $wp;
  }

  public function init(): void {
    $this->daemonRunAction->init();
    $this->daemonTriggerAction->init();
    $this->remoteExecutorHandler->init();
  }

  public function deactivate(): void {
    $this->actionScheduler->unscheduleAction(DaemonTrigger::NAME);
    $this->actionScheduler->unscheduleAction(DaemonRun::NAME);
  }

  /**
   * Unschedule all MailPoet actions when next "trigger" action is processed.
   * Note: We can't unschedule the actions directly inside the trigger action process, because the action is recurring and would schedule itself.
   * We need to do it after the action scheduler process.
   */
  public function deactivateOnTrigger(): void {
    $this->wp->addAction(DaemonTrigger::NAME, [$this, 'deactivateAfterProcess']);
  }

  public function deactivateAfterProcess(): void {
    $this->wp->addAction('action_scheduler_after_process_queue', [$this, 'deactivate']);
  }
}
