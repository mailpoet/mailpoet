<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Engine\Workflows\Trigger;

class TriggerHandler {
  /** @var ActionScheduler */
  private $actionScheduler;

  /** @var WordPress */
  private $wordPress;

  /** @var WorkflowStorage */
  private $workflowStorage;

  public function __construct(
    ActionScheduler $actionScheduler,
    WordPress $wordPress,
    WorkflowStorage $workflowStorage
  ) {
    $this->actionScheduler = $actionScheduler;
    $this->wordPress = $wordPress;
    $this->workflowStorage = $workflowStorage;
  }

  public function initialize(): void {
    $this->wordPress->addAction(Hooks::TRIGGER, [$this, 'processTrigger']);
  }

  public function processTrigger(Trigger $trigger): void {
    $workflows = $this->workflowStorage->getActiveWorkflowsByTrigger($trigger);
    foreach ($workflows as $workflow) {
      $step = $workflow->getTrigger($trigger->getKey());
      if (!$step) {
        throw Exceptions::workflowTriggerNotFound($workflow->getId(), $trigger->getKey());
      }

      // TODO: create new workflow run
    }
  }
}
