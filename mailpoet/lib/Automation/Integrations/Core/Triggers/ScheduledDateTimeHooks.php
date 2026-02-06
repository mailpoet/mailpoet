<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core\Triggers;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Engine\WordPress;

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
    $this->wp->addAction('mailpoet/automation/before_save', [$this, 'handleBeforeSave']);
  }

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

  private function cancelJob(Automation $automation): void {
    $args = [$automation->getId(), 0];
    $this->actionScheduler->unscheduleAction(
      ScheduledDateTimeTrigger::SCHEDULED_HOOK,
      $args
    );
  }

  private function getPreviousAutomation(Automation $automation): ?Automation {
    try {
      return $this->automationStorage->getAutomation($automation->getId());
    } catch (\Throwable $e) {
      return null;
    }
  }
}
