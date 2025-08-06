<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Registry;

class UpdateStepsController {
  /** @var Registry */
  private $registry;

  public function __construct(
    Registry $registry
  ) {
    $this->registry = $registry;
  }

  public function updateSteps(Automation $automation, array $data): Automation {
    $steps = [];
    foreach ($data as $index => $stepData) {
      $step = $this->processStep($stepData, $automation->getStep($stepData['id']));
      $updatedStep = $this->maybeRunOnDuplicate($step);
      $steps[$index] = $updatedStep;
    }
    $automation->setSteps($steps);
    return $automation;
  }

  private function maybeRunOnDuplicate(Step $step): Step {
    if ($step->getType() === 'action') {
      $args = $step->getArgs();
      $isStepDuplicated = !empty($args['stepDuplicated']);
      if ($isStepDuplicated) {
        $action = $this->registry->getAction($step->getKey());
        if ($action) {
          $duplicatedStep = $action->onDuplicate($step);
          $dupArgs = $duplicatedStep->getArgs();
          unset($dupArgs['stepDuplicated']);
          $duplicatedStep = new Step(
            $duplicatedStep->getId(),
            $duplicatedStep->getType(),
            $duplicatedStep->getKey(),
            $dupArgs,
            $duplicatedStep->getNextSteps(),
            $duplicatedStep->getFilters()
          );
          return $duplicatedStep;
        }
      }
    }
    return $step;
  }

  private function processStep(array $data, ?Step $existingStep): Step {
    $key = $data['key'];
    $step = $this->registry->getStep($key);
    if (!$step && $existingStep && $data !== $existingStep->toArray()) {
      throw Exceptions::automationStepNotFound($key);
    }
    return Step::fromArray($data);
  }
}
