<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core\Triggers;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
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

  /** @var AutomationStorage */
  private $automationStorage;

  public function __construct(
    WordPress $wp,
    ActionScheduler $actionScheduler,
    AutomationStorage $automationStorage
  ) {
    $this->wp = $wp;
    $this->actionScheduler = $actionScheduler;
    $this->automationStorage = $automationStorage;
  }

  public function init(): void {
    $this->wp->addAction(Hooks::AUTOMATION_BEFORE_SAVE, [$this, 'handleBeforeSave']);
  }

  /**
   * Detect automation status transitions and schedule/cancel AS jobs accordingly.
   *
   * This hook fires before the automation is persisted, so we compare the incoming
   * status with the previously stored version to detect transitions:
   * - draft → active: schedule the AS job
   * - active → draft/trash: cancel the AS job
   * - active → active (no change): do nothing
   */
  public function handleBeforeSave(Automation $automation): void {
    $trigger = $automation->getTrigger(ScheduledDateTimeTrigger::KEY);
    if (!$trigger) {
      return;
    }

    $previousAutomation = $this->getPreviousAutomation($automation);
    $wasActive = $previousAutomation && $previousAutomation->getStatus() === Automation::STATUS_ACTIVE;
    $isActive = $automation->getStatus() === Automation::STATUS_ACTIVE;

    if ($isActive && !$wasActive) {
      $this->scheduleJob($automation);
    } elseif ($wasActive && !$isActive) {
      $this->cancelJob($automation);
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

    $scheduledDate = new DateTimeImmutable($scheduledAt);
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

    foreach ($actions as $actionId => $action) {
      $args = $action->get_args();
      if (isset($args[0]) && (int)$args[0] === $automationId) {
        $this->actionScheduler->unscheduleAction(
          ScheduledDateTimeTrigger::SCHEDULED_HOOK,
          $args
        );
      }
    }
  }

  /**
   * Load the previously persisted version of the automation from storage.
   * Returns null for new automations (getId() throws) or if not found in DB.
   * We catch Throwable because getId() throws InvalidStateException when unset.
   */
  private function getPreviousAutomation(Automation $automation): ?Automation {
    try {
      return $this->automationStorage->getAutomation($automation->getId());
    } catch (\Throwable $e) {
      return null;
    }
  }
}
