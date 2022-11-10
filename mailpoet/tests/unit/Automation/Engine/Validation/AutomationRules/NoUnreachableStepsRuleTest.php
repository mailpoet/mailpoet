<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;

class NoUnreachableStepsRuleTest extends AutomationRuleTest {
  public function testItDetectsUnreachableSteps(): void {
    $automation = $this->createAutomation([
      'root' => ['a'],
      'a' => [],
      'b' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Unreachable steps found in automation graph');
    (new AutomationWalker())->walk($automation, [new NoUnreachableStepsRule()]);
  }

  public function testItDetectsUnreachableSubgraphs(): void {
    $automation = $this->createAutomation([
      'root' => ['a'],
      'a' => [],
      'b' => ['c'],
      'c' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Unreachable steps found in automation graph');
    $walker = new AutomationWalker();
    (new AutomationWalker())->walk($automation, [new NoUnreachableStepsRule()]);
  }

  public function testItPassesWithSimplePath(): void {
    $automation = $this->createAutomation([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => [],
    ]);

    (new AutomationWalker())->walk($automation, [new NoUnreachableStepsRule()]);
    // no exception thrown
  }
}
