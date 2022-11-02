<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

require_once __DIR__ . '/WorkflowRuleTest.php';

use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;

class NoUnreachableStepsRuleTest extends WorkflowRuleTest {
  public function testItDetectsUnreachableSteps(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a'],
      'a' => [],
      'b' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Unreachable steps found in automation graph');
    (new WorkflowWalker())->walk($workflow, [new NoUnreachableStepsRule()]);
  }

  public function testItDetectsUnreachableSubgraphs(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a'],
      'a' => [],
      'b' => ['c'],
      'c' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Unreachable steps found in automation graph');
    $walker = new WorkflowWalker();
    (new WorkflowWalker())->walk($workflow, [new NoUnreachableStepsRule()]);
  }

  public function testItPassesWithSimplePath(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => [],
    ]);

    (new WorkflowWalker())->walk($workflow, [new NoUnreachableStepsRule()]);
    // no exception thrown
  }
}
