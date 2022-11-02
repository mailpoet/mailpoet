<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowGraph;

use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoetUnitTest;

class WorkflowWalkerTest extends MailPoetUnitTest {
  public function testRootStepMissing(): void {
    $workflow = $this->createWorkflow([]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Invalid automation structure: Automation must contain a 'root' step");
    $this->walkWorkflow($workflow);
  }

  public function testNonRootStepMissing(): void {
    $workflow = $this->createWorkflow(['root' => ['a']]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Invalid automation structure: Step with ID 'a' not found (referenced from 'root')");
    $this->walkWorkflow($workflow);
  }

  public function testSimpleWorkflow(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => [],
    ]);

    $path = $this->walkWorkflow($workflow);
    $this->assertSame([
      ['root', []],
      ['a', ['root']],
      ['b', ['root', 'a']],
      ['c', ['root', 'a', 'b']],
    ], $path);
  }

  public function testMultiBranchWorkflow(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a1', 'a2'],
      'a1' => ['b1', 'b2'],
      'a2' => ['c'],
      'b1' => ['d'],
      'b2' => [],
      'c' => [],
      'd' => [],
    ]);

    $path = $this->walkWorkflow($workflow);
    $this->assertSame([
      ['root', []],
      ['a1', ['root']],
      ['b1', ['root', 'a1']],
      ['d', ['root', 'a1', 'b1']],
      ['b2', ['root', 'a1']],
      ['a2', ['root']],
      ['c', ['root', 'a2']],
    ], $path);
  }


  public function testCyclicWorkflow(): void {
    $workflow = $this->createWorkflow([
      'root' => ['a', 'root'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => ['a', 'd'],
      'd' => ['d'],
    ]);

    $path = $this->walkWorkflow($workflow);
    $this->assertSame([
      ['root', []],
      ['a', ['root']],
      ['b', ['root', 'a']],
      ['c', ['root', 'a', 'b']],
      ['d', ['root', 'a', 'b', 'c']],
    ], $path);
  }

  private function createStep(string $id, array $nextStepIds): Step {
    return new Step(
      $id,
      'test-type',
      'test-key',
      [],
      array_map(function (string $id) {
        return new NextStep($id);
      }, $nextStepIds));
  }

  private function createWorkflow(array $steps): Workflow {
    $stepMap = [];
    foreach ($steps as $id => $nextStepIds) {
      $stepMap[$id] = $this->createStep($id, $nextStepIds);
    }
    return $this->make(Workflow::class, ['getSteps' => $stepMap]);
  }

  private function walkWorkflow(Workflow $workflow): array {
    $visitor = new class implements WorkflowNodeVisitor {
      public $nodes = [];

      public function initialize(Workflow $workflow): void {
        $this->nodes = [];
      }

      public function visitNode(Workflow $workflow, WorkflowNode $node): void {
        $this->nodes[] = $node;
      }

      public function complete(Workflow $workflow): void {}
    };

    $walker = new WorkflowWalker();
    $walker->walk($workflow, [$visitor]);
    return array_map(function (WorkflowNode $node) {
      return [$node->getStep()->getId(), array_map(function (Step $parent) {
        return $parent->getId();
      }, $node->getParents())];
    }, $visitor->nodes);
  }
}
