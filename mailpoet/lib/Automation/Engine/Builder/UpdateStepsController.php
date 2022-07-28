<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;

class UpdateStepsController {
  public function updateSteps(Workflow $workflow, array $data): Workflow {
    $steps = [];
    foreach ($data as $stepData) {
      $step = $this->processStep($stepData);
      $steps[$step->getId()] = $step;
    }
    $workflow->setSteps($steps);
    return $workflow;
  }

  private function processStep(array $data): Step {
    return new Step(
      $data['id'],
      $data['type'],
      $data['key'],
      $data['next_step_id'] ?? null,
      $data['args'] ?? null
    );
  }
}
