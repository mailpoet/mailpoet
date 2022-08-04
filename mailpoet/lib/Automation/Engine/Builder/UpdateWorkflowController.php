<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;

class UpdateWorkflowController {
  /** @var WorkflowStorage */
  private $storage;

  public function __construct(
    WorkflowStorage $storage
  ) {
    $this->storage = $storage;
  }

  public function updateWorkflow(int $id, array $data): Workflow {
    // TODO: data & workflow validation (trigger existence, graph consistency, etc.)
    // TODO: new revisions when content is changed
    // TODO: validation when status being is changed

    $workflow = $this->storage->getWorkflow($id);
    if (!$workflow) {
      throw Exceptions::workflowNotFound($id);
    }

    $changed = false;

    if (array_key_exists('name', $data)) {
      $workflow->setName($data['name']);
      $changed = true;
    }

    if (array_key_exists('status', $data)) {
      $this->checkWorkflowStatus($data['status']);
      $workflow->setStatus($data['status']);
      $changed = true;
    }

    if (array_key_exists('steps', $data)) {
      $this->validateWorkflowSteps($workflow, $data['steps']);
      $steps = [];
      foreach ($data['steps'] as $step) {
        $steps[(string)$step['id']] = new Step(
          $step['id'],
          $step['type'],
          $step['key'],
          $step['next_step_id'] ?? null,
          $step['args'] ?? null
        );
      }
      $workflow->setSteps($steps);
      $changed = true;
    }

    if ($changed) {
      $this->storage->updateWorkflow($workflow);
    }

    $workflow = $this->storage->getWorkflow($id);
    if (!$workflow) {
      throw Exceptions::workflowNotFound($id);
    }
    return $workflow;
  }

  private function checkWorkflowStatus(string $status): void {
    if (!in_array($status, [Workflow::STATUS_ACTIVE, Workflow::STATUS_INACTIVE, Workflow::STATUS_DRAFT], true)) {
      // translators: %s is the status.
      throw UnexpectedValueException::create()->withMessage(sprintf(__('Invalid status: %s', 'mailpoet'), $status));
    }
  }

  private function validateWorkflowSteps(Workflow $workflow, array $steps): void {
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
