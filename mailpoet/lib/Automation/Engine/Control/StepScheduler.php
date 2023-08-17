<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;

class StepScheduler {
  /** @var ActionScheduler */
  private $actionScheduler;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  public function __construct(
    ActionScheduler $actionScheduler,
    AutomationRunStorage $automationRunStorage
  ) {
    $this->actionScheduler = $actionScheduler;
    $this->automationRunStorage = $automationRunStorage;
  }

  public function scheduleNextStep(StepRunArgs $args, int $timestamp = null): int {
    $runId = $args->getAutomationRun()->getId();

    // complete the automation run if there are no more steps
    if (count($args->getStep()->getNextSteps()) === 0) {
      $this->automationRunStorage->updateNextStep($runId, null);
      $this->automationRunStorage->updateStatus($runId, AutomationRun::STATUS_COMPLETE);
      return 0;
    }

    $nextStepId = $args->getStep()->getNextSteps()[0]->getId();
    $data = $this->getActionData($runId, $nextStepId);
    $id = $this->scheduleStepAction($data, $timestamp);
    $this->automationRunStorage->updateNextStep($runId, $nextStepId);
    return $id;
  }

  public function hasScheduledNextStep(StepRunArgs $args): bool {
    $runId = $args->getAutomationRun()->getId();
    foreach ($args->getStep()->getNextSteps() as $nextStep) {
      $data = $this->getActionData($runId, $nextStep->getId());
      $hasStep = $this->actionScheduler->hasScheduledAction(Hooks::AUTOMATION_STEP, $data);
      if ($hasStep) {
        return true;
      }
    }
    return false;
  }

  private function scheduleStepAction(array $data, int $timestamp = null): int {
    return $timestamp === null
      ? $this->actionScheduler->enqueue(Hooks::AUTOMATION_STEP, $data)
      : $this->actionScheduler->schedule($timestamp, Hooks::AUTOMATION_STEP, $data);
  }

  private function getActionData(int $runId, string $stepId): array {
    return [
      [
        'automation_run_id' => $runId,
        'step_id' => $stepId,
      ],
    ];
  }
}
