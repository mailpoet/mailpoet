<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

require_once __DIR__ . '/WorkflowRuleTest.php';

use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;

class NoJoinRuleTest extends WorkflowRuleTest {
  public function testItDetectsJoinedPath(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a1', 'a2'],
      'a1' => ['b'],
      'a2' => ['b'],
      'b' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Path join found in automation graph');
    (new WorkflowWalker())->walk($workflow, [new NoJoinRule()]);
  }

  public function testItDetectsLongJoinedPath(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a1', 'a2'],
      'a1' => ['b1'],
      'a2' => ['b2'],
      'b1' => ['c1'],
      'b2' => ['c2'],
      'c1' => ['d'],
      'c2' => ['d'],
      'd' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Path join found in automation graph');
    (new WorkflowWalker())->walk($workflow, [new NoJoinRule()]);
  }

  public function testItDetectsJoinedPathToSelf(): void {
    $workflow = $this->createWorkflow([
      'root' => ['root', 'root'],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Path join found in automation graph');
    (new WorkflowWalker())->walk($workflow, [new NoJoinRule()]);
  }

  public function testItPassesWithSimplePath(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => [],
    ]);

    (new WorkflowWalker())->walk($workflow, [new NoJoinRule()]);
    // no exception thrown
  }

  public function testItPassesWithPathSplit(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a1', 'a1'],
      'a1' => [],
      'a2' => [],
    ]);

    (new WorkflowWalker())->walk($workflow, [new NoJoinRule()]);
    // no exception thrown
  }
}
