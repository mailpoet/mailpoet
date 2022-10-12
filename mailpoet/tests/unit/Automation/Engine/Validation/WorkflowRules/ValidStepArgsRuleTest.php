<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

require_once __DIR__ . '/WorkflowRuleTest.php';

use Codeception\Stub\Expected;
use MailPoet\Automation\Engine\Control\RootStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;
use MailPoet\Validator\Validator;

class ValidStepArgsRuleTest extends WorkflowRuleTest {
  public function testItRunsArgsValidation(): void {
    $registry = $this->make(Registry::class, [
      'steps' => ['core:root' => new RootStep()],
    ]);

    $validator = $this->make(Validator::class, [
      'validate' => Expected::once(),
    ]);

    $rule = new ValidStepArgsRule($registry, $validator);
    $workflow = $this->getWorkflow();
    (new WorkflowWalker())->walk($workflow, [$rule]);
  }

  public function testItSkipsArgsValidationForNonExistentStep(): void {
    $registry = $this->make(Registry::class);
    $validator = $this->make(Validator::class, [
      'validate' => Expected::never(),
    ]);

    $rule = new ValidStepArgsRule($registry, $validator);
    $workflow = $this->getWorkflow();
    (new WorkflowWalker())->walk($workflow, [$rule]);
  }

  private function getWorkflow(): Workflow {
    return $this->make(Workflow::class, [
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], []),
      ],
    ]);
  }
}
