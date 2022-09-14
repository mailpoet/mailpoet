<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

require_once __DIR__ . '/WorkflowRuleTest.php';

use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;

class NoSplitRuleTest extends WorkflowRuleTest {
  public function testItDetectsSplitPath(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a1', 'a2'],
      'a1' => [],
      'a2' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid workflow structure: Path split found in workflow graph');
    (new WorkflowWalker())->walk($workflow, [new NoSplitRule()]);
  }

  public function testItDetectsSplitPathWithSelfLoop(): void {
    $workflow = $this->createWorkflow([
      'root' => ['root', 'a'],
      'a' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid workflow structure: Path split found in workflow graph');
    (new WorkflowWalker())->walk($workflow, [new NoSplitRule()]);
  }

  public function testItPassesWithSimplePath(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => [],
    ]);

    (new WorkflowWalker())->walk($workflow, [new NoSplitRule()]);
    // no exception thrown
  }

  public function testItPassesWithJoinedPath(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['a'],
    ]);

    (new WorkflowWalker())->walk($workflow, [new NoSplitRule()]);
    // no exception thrown
  }
}
