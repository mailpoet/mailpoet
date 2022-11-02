<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\WorkflowStatisticsStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Validation\WorkflowValidator;

class UpdateWorkflowController {
  /** @var Hooks */
  private $hooks;

  /** @var WorkflowStorage */
  private $storage;

  /** @var WorkflowStatisticsStorage */
  private $statisticsStorage;

  /** @var WorkflowValidator */
  private $workflowValidator;

  /** @var UpdateStepsController */
  private $updateStepsController;

  public function __construct(
    Hooks $hooks,
    WorkflowStorage $storage,
    WorkflowStatisticsStorage $statisticsStorage,
    WorkflowValidator $workflowValidator,
    UpdateStepsController $updateStepsController
  ) {
    $this->hooks = $hooks;
    $this->storage = $storage;
    $this->statisticsStorage = $statisticsStorage;
    $this->workflowValidator = $workflowValidator;
    $this->updateStepsController = $updateStepsController;
  }

  public function updateWorkflow(int $id, array $data): Workflow {
    $workflow = $this->storage->getWorkflow($id);
    if (!$workflow) {
      throw Exceptions::workflowNotFound($id);
    }
    $this->validateIfWorkflowCanBeUpdated($workflow, $data);

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

    $this->workflowValidator->validate($workflow);
    $this->storage->updateWorkflow($workflow);

    $workflow = $this->storage->getWorkflow($id);
    if (!$workflow) {
      throw Exceptions::workflowNotFound($id);
    }
    return $workflow;
  }

  /**
   * This is a temporary validation, see MAILPOET-4744
   */
  private function validateIfWorkflowCanBeUpdated(Workflow $workflow, array $data): void {

    if (
      !in_array(
      $workflow->getStatus(),
      [
        Workflow::STATUS_ACTIVE,
        Workflow::STATUS_DEACTIVATING,
      ],
      true
      )
    ) {
      return;
    }

    $statistics = $this->statisticsStorage->getWorkflowStats($workflow->getId());
    if ($statistics->getInProgress() === 0) {
      return;
    }

    if (!isset($data['status']) || $data['status'] === $workflow->getStatus()) {
      throw Exceptions::workflowHasActiveRuns($workflow->getId());
    }
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
      if (!$existingStep || !$this->stepChanged(Step::fromArray($data), $existingStep)) {
        throw Exceptions::workflowStructureModificationNotSupported();
      }
    }
  }

  private function stepChanged(Step $a, Step $b): bool {
    $aData = $a->toArray();
    $bData = $b->toArray();
    unset($aData['args']);
    unset($bData['args']);
    return $aData === $bData;
  }
}
