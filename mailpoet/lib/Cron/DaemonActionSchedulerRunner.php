<?php declare(strict_types=1);

namespace MailPoet\Cron;

use MailPoet\Cron\ActionScheduler\Actions\DaemonRun;
use MailPoet\Cron\ActionScheduler\Actions\DaemonTrigger;
use MailPoet\Cron\ActionScheduler\ActionScheduler;
use MailPoet\Cron\ActionScheduler\RemoteExecutorHandler;

class DaemonActionSchedulerRunner {
  /** @var ActionScheduler */
  private $actionScheduler;

  /** @var RemoteExecutorHandler */
  private $remoteExecutorHandler;

  /** @var DaemonTrigger */
  private $daemonTriggerAction;

  /** @var DaemonRun */
  private $daemonRunAction;

  public function __construct(
    ActionScheduler $actionScheduler,
    RemoteExecutorHandler $remoteExecutorHandler,
    DaemonTrigger $daemonTriggerAction,
    DaemonRun $daemonRunAction
  ) {
    $this->actionScheduler = $actionScheduler;
    $this->remoteExecutorHandler = $remoteExecutorHandler;
    $this->daemonTriggerAction = $daemonTriggerAction;
    $this->daemonRunAction = $daemonRunAction;
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
}
