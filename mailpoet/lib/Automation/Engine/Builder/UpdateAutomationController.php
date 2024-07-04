<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use ActionScheduler_Store;
use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStatisticsStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Engine\Validation\AutomationValidator;

class UpdateAutomationController {
  /** @var Hooks */
  private $hooks;

  /** @var AutomationStorage */
  private $storage;

  /** @var AutomationStatisticsStorage */
  private $statisticsStorage;

  /** @var AutomationValidator */
  private $automationValidator;

  /** @var UpdateStepsController */
  private $updateStepsController;

  private AutomationRunStorage $automationRunStorage;

  private ActionScheduler $actionScheduler;

  public function __construct(
    Hooks $hooks,
    AutomationStorage $storage,
    AutomationStatisticsStorage $statisticsStorage,
    AutomationValidator $automationValidator,
    AutomationRunStorage $automationRunStorage,
    ActionScheduler $actionScheduler,
    UpdateStepsController $updateStepsController
  ) {
    $this->hooks = $hooks;
    $this->storage = $storage;
    $this->statisticsStorage = $statisticsStorage;
    $this->automationValidator = $automationValidator;
    $this->updateStepsController = $updateStepsController;
    $this->automationRunStorage = $automationRunStorage;
    $this->actionScheduler = $actionScheduler;
  }

  public function updateAutomation(int $id, array $data): Automation {
    $automation = $this->storage->getAutomation($id);
    if (!$automation) {
      throw Exceptions::automationNotFound($id);
    }
    $this->validateIfAutomationCanBeUpdated($automation, $data);

    if (array_key_exists('name', $data)) {
      $automation->setName($data['name']);
    }
    $originalStatus = $automation->getStatus();

    if (array_key_exists('status', $data)) {
      $this->checkAutomationStatus($data['status']);
      $automation->setStatus($data['status']);
    }

    if (array_key_exists('steps', $data)) {
      $this->validateAutomationSteps($automation, $data['steps']);
      $this->updateStepsController->updateSteps($automation, $data['steps']);
      foreach ($automation->getSteps() as $step) {
        $this->hooks->doAutomationStepBeforeSave($step, $automation);
        $this->hooks->doAutomationStepByKeyBeforeSave($step, $automation);
      }
    }

    if (($automation->getStatus() === Automation::STATUS_DRAFT) && ($originalStatus === Automation::STATUS_ACTIVE)) {
      $this->unscheduleAutomationRuns($automation);
    }

    if (array_key_exists('meta', $data)) {
      $automation->deleteAllMetas();
      foreach ($data['meta'] as $key => $value) {
        $automation->setMeta($key, $value);
      }
    }

    $this->hooks->doAutomationBeforeSave($automation);

    $this->automationValidator->validate($automation);
    $this->storage->updateAutomation($automation);

    $automation = $this->storage->getAutomation($id);
    if (!$automation) {
      throw Exceptions::automationNotFound($id);
    }
    return $automation;
  }

  /**
   * This is a temporary validation, see MAILPOET-4744
   */
  private function validateIfAutomationCanBeUpdated(Automation $automation, array $data): void {

    if (
      !in_array(
        $automation->getStatus(),
        [
        Automation::STATUS_ACTIVE,
        Automation::STATUS_DEACTIVATING,
        ],
        true
      )
    ) {
      return;
    }

    $statistics = $this->statisticsStorage->getAutomationStats($automation->getId());
    if ($statistics->getInProgress() === 0) {
      return;
    }

    if (!isset($data['status']) || $data['status'] === $automation->getStatus()) {
      throw Exceptions::automationHasActiveRuns($automation->getId());
    }
  }

  private function checkAutomationStatus(string $status): void {
    if (!in_array($status, Automation::STATUS_ALL, true)) {
      // translators: %s is the status.
      throw UnexpectedValueException::create()->withMessage(sprintf(__('Invalid status: %s', 'mailpoet'), $status));
    }
  }

  protected function validateAutomationSteps(Automation $automation, array $steps): void {
    $existingSteps = $automation->getSteps();
    if (count($steps) !== count($existingSteps)) {
      throw Exceptions::automationStructureModificationNotSupported();
    }

    foreach ($steps as $id => $data) {
      $existingStep = $existingSteps[$id] ?? null;
      if (!$existingStep || !$this->stepChanged(Step::fromArray($data), $existingStep)) {
        throw Exceptions::automationStructureModificationNotSupported();
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

  private function unscheduleAutomationRuns(Automation $automation): void {
    $runIds = [];
    $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
    foreach ($runs as $run) {
      if ($run->getStatus() === AutomationRun::STATUS_RUNNING) {
        $this->automationRunStorage->updateStatus($run->getId(), AutomationRun::STATUS_CANCELLED);
      }
      $runIds[$run->getId()] = $run;
    }

    $actions = $this->actionScheduler->getScheduledActions(['hook' => Hooks::AUTOMATION_STEP, 'status' => ActionScheduler_Store::STATUS_PENDING]);
    foreach ($actions as $action) {
      $args = $action->get_args();
      $automationArgs = reset($args);
      if (isset($automationArgs['automation_run_id']) && isset($runIds[$automationArgs['automation_run_id']])) {
        $this->actionScheduler->unscheduleAction(Hooks::AUTOMATION_STEP, $args);
      }
    }
  }
}
