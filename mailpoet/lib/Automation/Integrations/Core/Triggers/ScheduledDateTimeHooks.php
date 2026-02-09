<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core\Triggers;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\WordPress;

/**
 * Manages the Action Scheduler job lifecycle for the scheduled date/time trigger.
 *
 * Listens to the AUTOMATION_BEFORE_SAVE hook to detect activation/deactivation:
 * - When an automation with this trigger is activated → schedule an AS single action
 *   at the configured timestamp
 * - When deactivated → cancel the pending AS action
 *
 * This is separated from the Trigger class because lifecycle management (scheduling
 * on save) is a different concern from trigger execution (processing subscribers
 * when the job fires). The Trigger class handles execution; this class handles scheduling.
 */
class ScheduledDateTimeHooks {
  /** @var WordPress */
  private $wp;

  /** @var ActionScheduler */
  private $actionScheduler;

  public function __construct(
    WordPress $wp,
    ActionScheduler $actionScheduler
  ) {
    $this->wp = $wp;
    $this->actionScheduler = $actionScheduler;
  }

  public function init(): void {
    $this->wp->addAction(Hooks::AUTOMATION_BEFORE_SAVE, [$this, 'handleBeforeSave']);
  }

  public function handleBeforeSave(Automation $automation): void {
    $trigger = $automation->getTrigger(ScheduledDateTimeTrigger::KEY);
    if (!$trigger) {
      return;
    }

    // Always cancel existing jobs first to prevent duplicates
    $this->cancelJob($automation);

    // Schedule new job only if the automation is active
    if ($automation->getStatus() === Automation::STATUS_ACTIVE) {
      $this->scheduleJob($automation);
    }
  }

  /** Schedule an AS single action at the configured timestamp. Args [automationId, 0] where 0 is the initial batch offset. */
  private function scheduleJob(Automation $automation): void {
    $trigger = $automation->getTrigger(ScheduledDateTimeTrigger::KEY);
    if (!$trigger) {
      return;
    }

    $scheduledAt = $trigger->getArgs()['scheduled_at'] ?? null;
    if (!is_string($scheduledAt) || $scheduledAt === '') {
      return;
    }

    try {
      $scheduledDate = new DateTimeImmutable($scheduledAt);
    } catch (\Exception $e) {
      return;
    }
    $args = [$automation->getId(), 0];
    $this->actionScheduler->schedule(
      $scheduledDate->getTimestamp(),
      ScheduledDateTimeTrigger::SCHEDULED_HOOK,
      $args
    );
  }

  /**
   * Cancel all pending AS actions (initial + in-flight batch jobs) for this automation.
   * Batch jobs have varying cursor args [automationId, lastProcessedId], so we query
   * all pending actions for the hook and cancel those matching this automation's ID.
   */
  private function cancelJob(Automation $automation): void {
    $automationId = $automation->getId();
    $actions = $this->actionScheduler->getScheduledActions([
      'hook' => ScheduledDateTimeTrigger::SCHEDULED_HOOK,
      'status' => 'pending',
    ]);

    foreach ($actions as $action) {
      $args = $action->get_args();
      if (isset($args[0]) && (int)$args[0] === $automationId) {
        $this->actionScheduler->unscheduleAction(
          ScheduledDateTimeTrigger::SCHEDULED_HOOK,
          $args
        );
      }
    }
  }
}
