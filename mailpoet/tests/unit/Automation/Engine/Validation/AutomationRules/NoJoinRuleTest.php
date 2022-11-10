<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;

class NoJoinRuleTest extends AutomationRuleTest {
  public function testItDetectsJoinedPath(): void {
    $automation = $this->createAutomation([
      'root' => ['a1', 'a2'],
      'a1' => ['b'],
      'a2' => ['b'],
      'b' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Path join found in automation graph');
    (new AutomationWalker())->walk($automation, [new NoJoinRule()]);
  }

  public function testItDetectsLongJoinedPath(): void {
    $automation = $this->createAutomation([
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
    (new AutomationWalker())->walk($automation, [new NoJoinRule()]);
  }

  public function testItDetectsJoinedPathToSelf(): void {
    $automation = $this->createAutomation([
      'root' => ['root', 'root'],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Path join found in automation graph');
    (new AutomationWalker())->walk($automation, [new NoJoinRule()]);
  }

  public function testItPassesWithSimplePath(): void {
    $automation = $this->createAutomation([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => [],
    ]);

    (new AutomationWalker())->walk($automation, [new NoJoinRule()]);
    // no exception thrown
  }

  public function testItPassesWithPathSplit(): void {
    $automation = $this->createAutomation([
      'root' => ['a1', 'a1'],
      'a1' => [],
      'a2' => [],
    ]);

    (new AutomationWalker())->walk($automation, [new NoJoinRule()]);
    // no exception thrown
  }
}
