<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationGraph;

use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoetUnitTest;

class AutomationWalkerTest extends MailPoetUnitTest {
  public function testRootStepMissing(): void {
    $automation = $this->createAutomation([]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Invalid automation structure: Automation must contain a 'root' step");
    $this->walkAutomation($automation);
  }

  public function testNonRootStepMissing(): void {
    $automation = $this->createAutomation(['root' => ['a']]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Invalid automation structure: Step with ID 'a' not found (referenced from 'root')");
    $this->walkAutomation($automation);
  }

  public function testSimpleAutomation(): void {
    $automation = $this->createAutomation([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => [],
    ]);

    $path = $this->walkAutomation($automation);
    $this->assertSame([
      ['root', []],
      ['a', ['root']],
      ['b', ['root', 'a']],
      ['c', ['root', 'a', 'b']],
    ], $path);
  }

  public function testMultiBranchAutomation(): void {
    $automation = $this->createAutomation([
      'root' => ['a1', 'a2'],
      'a1' => ['b1', 'b2'],
      'a2' => ['c'],
      'b1' => ['d'],
      'b2' => [],
      'c' => [],
      'd' => [],
    ]);

    $path = $this->walkAutomation($automation);
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


  public function testCyclicAutomation(): void {
    $automation = $this->createAutomation([
      'root' => ['a', 'root'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => ['a', 'd'],
      'd' => ['d'],
    ]);

    $path = $this->walkAutomation($automation);
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

  private function createAutomation(array $steps): Automation {
    $stepMap = [];
    foreach ($steps as $id => $nextStepIds) {
      $stepMap[$id] = $this->createStep($id, $nextStepIds);
    }
    return $this->make(Automation::class, ['getSteps' => $stepMap]);
  }

  private function walkAutomation(Automation $automation): array {
    $visitor = new class implements AutomationNodeVisitor {
      public $nodes = [];

      public function initialize(Automation $automation): void {
        $this->nodes = [];
      }

      public function visitNode(Automation $automation, AutomationNode $node): void {
        $this->nodes[] = $node;
      }

      public function complete(Automation $automation): void {}
    };

    $walker = new AutomationWalker();
    $walker->walk($automation, [$visitor]);
    return array_map(function (AutomationNode $node) {
      return [$node->getStep()->getId(), array_map(function (Step $parent) {
        return $parent->getId();
      }, $node->getParents())];
    }, $visitor->nodes);
  }
}
