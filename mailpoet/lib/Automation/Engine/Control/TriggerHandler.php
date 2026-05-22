<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Engine\WordPress;

class TriggerHandler {
  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunCreator */
  private $automationRunCreator;

  /** @var WordPress */
  private $wordPress;

  public function __construct(
    AutomationStorage $automationStorage,
    AutomationRunCreator $automationRunCreator,
    WordPress $wordPress
  ) {
    $this->automationStorage = $automationStorage;
    $this->automationRunCreator = $automationRunCreator;
    $this->wordPress = $wordPress;
  }

  public function initialize(): void {
    $this->wordPress->addAction(Hooks::TRIGGER, [$this, 'processTrigger'], 10, 2);
  }

  /** @param Subject[] $subjects */
  public function processTrigger(Trigger $trigger, array $subjects): void {
    $automations = $this->automationStorage->getActiveAutomationsByTrigger($trigger);
    if (!$automations) {
      return;
    }

    $preparedSubjects = $this->automationRunCreator->prepareSubjects($subjects);
    foreach ($automations as $automation) {
      $this->automationRunCreator->createForAutomation(
        $automation,
        $trigger,
        $preparedSubjects['subjects'],
        [],
        $preparedSubjects['subject_entries']
      );
    }
  }
}
