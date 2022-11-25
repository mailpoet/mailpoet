<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoetUnitTest;

abstract class AutomationRuleTest extends MailPoetUnitTest {
  public function createAutomation(array $steps): Automation {
    $stepMap = [];
    foreach ($steps as $id => $nextStepIds) {
      $stepMap[$id] = $this->createStep($id, 'test-type', $nextStepIds);
    }
    return $this->make(Automation::class, ['getSteps' => $stepMap]);
  }

  public function createStep(string $id, string $type = 'test-type', array $nextStepIds = []): Step {
    $nextSteps = array_map(function (string $id) {
      return new NextStep($id);
    }, $nextStepIds);
    return new Step($id, $type, 'test-key', [], $nextSteps);
  }
}
