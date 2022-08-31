<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;

class UpdateWorkflowController {
  /** @var Hooks */
  private $hooks;

  /** @var WorkflowStorage */
  private $storage;

  /** @var UpdateStepsController */
  private $updateStepsController;

  public function __construct(
    Hooks $hooks,
    WorkflowStorage $storage,
    UpdateStepsController $updateStepsController
  ) {
    $this->hooks = $hooks;
    $this->storage = $storage;
    $this->updateStepsController = $updateStepsController;
  }

  public function updateWorkflow(int $id, array $data): Workflow {
    // TODO: data & workflow validation (trigger existence, graph consistency, etc.)
    // TODO: new revisions when content is changed
    // TODO: validation when status being is changed

    $workflow = $this->storage->getWorkflow($id);
    if (!$workflow) {
      throw Exceptions::workflowNotFound($id);
    }

    if (array_key_exists('name', $data)) {
      $workflow->setName($data['name']);
    }

    if (array_key_exists('status', $data)) {
      $this->checkWorkflowStatus($data['status']);
      $workflow->setStatus($data['status']);
    }

    if (array_key_exists('steps', $data)) {
      $this->validateWorkflowSteps($workflow, $data['steps']);
      $this->updateStepsController->updateSteps($workflow, $data['steps']);
      foreach ($workflow->getSteps() as $step) {
        $this->hooks->doWorkflowStepBeforeSave($step);
        $this->hooks->doWorkflowStepByKeyBeforeSave($step);
      }
    }

    $this->hooks->doWorkflowBeforeSave($workflow);
    $this->storage->updateWorkflow($workflow);

    $workflow = $this->storage->getWorkflow($id);
    if (!$workflow) {
      throw Exceptions::workflowNotFound($id);
    }
    return $workflow;
  }

  private function checkWorkflowStatus(string $status): void {
    if (!in_array($status, Workflow::STATUS_ALL, true)) {
      // translators: %s is the status.
      throw UnexpectedValueException::create()->withMessage(sprintf(__('Invalid status: %s', 'mailpoet'), $status));
    }
  }

  protected function validateWorkflowSteps(Workflow $workflow, array $steps): void {
    $existingSteps = $workflow->getSteps();
    if (count($steps) !== count($existingSteps)) {
      throw Exceptions::workflowStructureModificationNotSupported();
    }

    foreach ($steps as $id => $data) {
      $existingStep = $existingSteps[$id] ?? null;
      if (
        !$existingStep
        || $data['id'] !== $existingStep->getId()
        || $data['type'] !== $existingStep->getType()
        || $data['key'] !== $existingStep->getKey()
        || $data['next_step_id'] !== $existingStep->getNextStepId()
      ) {
        throw Exceptions::workflowStructureModificationNotSupported();
      }
    }
  }
}
