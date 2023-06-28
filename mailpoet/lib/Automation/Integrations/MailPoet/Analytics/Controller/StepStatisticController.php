<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Controller;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Entities\Query;

class StepStatisticController {


  private $automationRunStorage;

  public function __construct(
    AutomationRunStorage $automationRunStorage
  ) {
    $this->automationRunStorage = $automationRunStorage;
  }

  public function getWaitingStatistics(Automation $automation, Query $query): array {
    $rawData = $this->automationRunStorage->getAutomationStepStatisticForTimeFrame(
      $automation->getId(),
      AutomationRun::STATUS_RUNNING,
      $query->getAfter(),
      $query->getBefore()
    );
    $stepData = [];
    foreach ($rawData as $rawDatum) {
      $stepData[$rawDatum['next_step_id']] = (int)$rawDatum['count'];
    }
    $stepsWithValues = array_keys($stepData);

    $data = [];
    foreach ($automation->getSteps() as $step) {
      $nextStepIds = array_map(function(NextStep $step) { return $step->getId();

      }, $step->getNextSteps());
      $matchedSteps = array_intersect($nextStepIds, $stepsWithValues);
      foreach ($matchedSteps as $matchedStep) {
        if (!isset($data[$step->getId()])) {
          $data[$step->getId()] = 0;
        }
        $data[$step->getId()] += $stepData[$matchedStep];
      }
    }
    return $data;
  }
}
