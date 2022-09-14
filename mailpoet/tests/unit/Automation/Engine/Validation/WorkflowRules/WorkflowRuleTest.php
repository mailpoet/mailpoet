<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoetUnitTest;

abstract class WorkflowRuleTest extends MailPoetUnitTest {
  public function createWorkflow(array $steps): Workflow {
    $stepMap = [];
    foreach ($steps as $id => $nextStepIds) {
      $stepMap[$id] = $this->createStep($id, 'test-type', $nextStepIds);
    }
    return $this->make(Workflow::class, ['getSteps' => $stepMap]);
  }

  public function createStep(string $id, string $type = 'test-type', array $nextStepIds = []): Step {
    $nextSteps = array_map(function (string $id) {
      return new NextStep($id);
    }, $nextStepIds);
    return new Step($id, $type, 'test-key', [], $nextSteps);
  }
}
