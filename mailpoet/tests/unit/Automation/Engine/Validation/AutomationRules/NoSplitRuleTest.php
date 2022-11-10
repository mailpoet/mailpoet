<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;

class NoSplitRuleTest extends AutomationRuleTest {
  public function testItDetectsSplitPath(): void {
    $automation = $this->createAutomation([
      'root' => ['a1', 'a2'],
      'a1' => [],
      'a2' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Path split found in automation graph');
    (new AutomationWalker())->walk($automation, [new NoSplitRule()]);
  }

  public function testItDetectsSplitPathWithSelfLoop(): void {
    $automation = $this->createAutomation([
      'root' => ['root', 'a'],
      'a' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Path split found in automation graph');
    (new AutomationWalker())->walk($automation, [new NoSplitRule()]);
  }

  public function testItPassesWithSimplePath(): void {
    $automation = $this->createAutomation([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => [],
    ]);

    (new AutomationWalker())->walk($automation, [new NoSplitRule()]);
    // no exception thrown
  }

  public function testItPassesWithJoinedPath(): void {
    $automation = $this->createAutomation([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['a'],
    ]);

    (new AutomationWalker())->walk($automation, [new NoSplitRule()]);
    // no exception thrown
  }
}
