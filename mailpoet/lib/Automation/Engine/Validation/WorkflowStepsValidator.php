<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Validator\Validator;

class WorkflowStepsValidator {
  /** @var Registry */
  private $registry;

  /** @var Validator */
  private $validator;

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var Workflow|null|false */
  private $cachedExistingWorkflow = false;

  public function __construct(
    Registry $registry,
    Validator $validator,
    WorkflowStorage $workflowStorage
  ) {
    $this->validator = $validator;
    $this->registry = $registry;
    $this->workflowStorage = $workflowStorage;
  }

  public function validateSteps(Workflow $workflow): void {
    foreach ($workflow->getSteps() as $step) {
      $this->validateStep($workflow, $step);
    }
  }

  private function validateStep(Workflow $workflow, Step $step): void {
    $registryStep = $this->registry->getStep($step->getKey());
    if (!$registryStep) {
      // step not registered (e.g. plugin was deactivated) - allow saving it only if it hasn't changed
      $currentWorkflow = $this->getCurrentWorkflow($workflow);
      $currentStep = $currentWorkflow ? ($currentWorkflow->getSteps()[$step->getId()] ?? null) : null;
      if (!$currentStep || $step->toArray() !== $currentStep->toArray()) {
        throw Exceptions::workflowStepModifiedWhenUnknown($step);
      }
      return;
    }

    // full validation for active workflows
    if ($workflow->getStatus() === Workflow::STATUS_ACTIVE) {
      $this->validator->validate($registryStep->getArgsSchema(), $step->getArgs());
    }
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
