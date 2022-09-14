<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

require_once __DIR__ . '/WorkflowRuleTest.php';

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;

class ConsistentStepMapRuleTest extends WorkflowRuleTest {
  public function testItDetectsWrongKeyValuePair(): void {
    $workflow = $this->make(Workflow::class, ['getSteps' => [
      'root' => $this->createStep('a'),
    ]]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid workflow structure: TODO');
    (new WorkflowWalker())->walk($workflow, [new ConsistentStepMapRule()]);
  }

  public function testItPassesWithCorrectKeyValuePair(): void {
    $workflow = $this->make(Workflow::class, ['getSteps' => [
      'root' => $this->createStep('root'),
    ]]);

    (new WorkflowWalker())->walk($workflow, [new ConsistentStepMapRule()]);
    // no exception thrown
  }
}
