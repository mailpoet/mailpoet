<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

require_once __DIR__ . '/WorkflowRuleTest.php';

use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;

class NoCycleRuleTest extends WorkflowRuleTest {
  public function testItDetectsLongCycle(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => ['a'],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid workflow structure: Cycle found in workflow graph');
    (new WorkflowWalker())->walk($workflow, [new NoCycleRule()]);
  }

  public function testItDetectsSelfCycle(): void {
    $workflow = $this->createWorkflow([
      'root' => ['root'],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid workflow structure: Cycle found in workflow graph');
    (new WorkflowWalker())->walk($workflow, [new NoCycleRule()]);
  }

  public function testItPassesWithSimplePath(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => [],
    ]);

    (new WorkflowWalker())->walk($workflow, [new NoCycleRule()]);
    // no exception thrown
  }

  public function testItPassesWithPathSplitAndJoin(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a1', 'a2'],
      'a1' => ['b'],
      'a2' => ['b'],
      'b' => [],
    ]);

    (new WorkflowWalker())->walk($workflow, [new NoCycleRule()]);
    // no exception thrown
  }
}
