<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;

class UnknownStepRule implements WorkflowNodeVisitor {
  /** @var Registry */
  private $registry;

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var Workflow|null|false */
  private $cachedExistingWorkflow = false;

  public function __construct(
    Registry $registry,
    WorkflowStorage $workflowStorage
  ) {
    $this->registry = $registry;
    $this->workflowStorage = $workflowStorage;
  }

  public function initialize(Workflow $workflow): void {
    $this->cachedExistingWorkflow = false;
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    $step = $node->getStep();
    $registryStep = $this->registry->getStep($step->getKey());

    // step not registered (e.g. plugin was deactivated) - allow saving it only if it hasn't changed
    if (!$registryStep) {
      $currentWorkflow = $this->getCurrentWorkflow($workflow);
      $currentStep = $currentWorkflow ? ($currentWorkflow->getSteps()[$step->getId()] ?? null) : null;
      if (!$currentStep || $step->toArray() !== $currentStep->toArray()) {
        throw Exceptions::workflowStepModifiedWhenUnknown($step);
      }
    }
  }

  public function complete(Workflow $workflow): void {
  }

  private function getCurrentWorkflow(Workflow $workflow): ?Workflow {
    try {
      $id = $workflow->getId();
      if ($this->cachedExistingWorkflow === false) {
        $this->cachedExistingWorkflow = $this->workflowStorage->getWorkflow($id);
      }
    } catch (InvalidStateException $e) {
      // for new workflows, no workflow ID is set
      $this->cachedExistingWorkflow = null;
    }
    return $this->cachedExistingWorkflow;
  }
}
