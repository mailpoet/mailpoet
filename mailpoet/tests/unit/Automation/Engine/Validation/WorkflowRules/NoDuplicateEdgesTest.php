<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

require_once __DIR__ . '/WorkflowRuleTest.php';

use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;

class NoDuplicateEdgesTest extends WorkflowRuleTest {
  public function testItDetectsDuplicateEdges(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a', 'a'],
      'a' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Duplicate next step definition found');
    (new WorkflowWalker())->walk($workflow, [new NoDuplicateEdgesRule()]);
  }

  public function testItPassesWithSingleEdges(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => [],
    ]);

    (new WorkflowWalker())->walk($workflow, [new NoDuplicateEdgesRule()]);
    // no exception thrown
  }
}
