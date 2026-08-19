<?php declare(strict_types = 1);

namespace MailPoet\Cron;

use MailPoet\Cron\ActionScheduler\Actions\DaemonRun;
use MailPoet\Cron\ActionScheduler\Actions\DaemonTrigger;
use MailPoet\Cron\ActionScheduler\ActionScheduler;
use MailPoet\Cron\ActionScheduler\RemoteExecutorHandler;
use MailPoet\WP\Functions as WPFunctions;

class DaemonActionSchedulerRunner {
  public const DEACTIVATION_FLAG_OPTION = 'mailpoet_cron_deactivating';

  // The flag guards a short race window while cron actions are being unscheduled.
  // An older flag is stale — e.g. left behind by an update flow that died before
  // rescheduling — and must not block the daemon trigger forever.
  public const DEACTIVATION_FLAG_TTL = 5 * 60;

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

  public function init(bool $isActive = true): void {
    if (!$isActive) {
      $this->deactivateOnTrigger();
      return;
    }
    $this->daemonRunAction->init();
    $this->daemonTriggerAction->init();
    $this->remoteExecutorHandler->init();
  }

  public function deactivate(): void {
    // Set flag BEFORE unscheduling to prevent race condition with parallel requests
    $this->wp->updateOption(self::DEACTIVATION_FLAG_OPTION, $this->wp->currentTime('timestamp', true));
    $this->actionScheduler->unscheduleAllCronActions();
  }

  public function clearDeactivationFlag(): void {
    $this->wp->deleteOption(self::DEACTIVATION_FLAG_OPTION);
  }

  public function isDeactivating(): bool {
    return self::isDeactivationFlagFresh($this->wp);
  }

  // Static so DaemonTrigger can use it without a circular dependency.
  // Legacy boolean values are treated as stale timestamps and expire immediately.
  public static function isDeactivationFlagFresh(WPFunctions $wp): bool {
    $flagValue = $wp->getOption(self::DEACTIVATION_FLAG_OPTION, false);
    if (!$flagValue) {
      return false;
    }
    return (int)$flagValue > $wp->currentTime('timestamp', true) - self::DEACTIVATION_FLAG_TTL;
  }

  /**
   * Unschedule all MailPoet actions when next "trigger" action is processed.
   * Note: We can't unschedule the actions directly inside the trigger action itself,
   * because the action is recurring and would reschedule itself anyway.
   * We need do the deactivation after the action scheduler process finishes.
   */
  private function deactivateOnTrigger(): void {
    $this->wp->addAction(DaemonTrigger::NAME, [$this, 'deactivateAfterProcess']);
  }

  public function deactivateAfterProcess(): void {
    $this->wp->addAction('action_scheduler_after_process_queue', [$this, 'deactivate']);
  }
}
