<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;

class NoCycleRuleTest extends AutomationRuleTest {
  public function testItDetectsLongCycle(): void {
    $automation = $this->createAutomation([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => ['a'],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Cycle found in automation graph');
    (new AutomationWalker())->walk($automation, [new NoCycleRule()]);
  }

  public function testItDetectsSelfCycle(): void {
    $automation = $this->createAutomation([
      'root' => ['root'],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Cycle found in automation graph');
    (new AutomationWalker())->walk($automation, [new NoCycleRule()]);
  }

  public function testItPassesWithSimplePath(): void {
    $automation = $this->createAutomation([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => [],
    ]);

    (new AutomationWalker())->walk($automation, [new NoCycleRule()]);
    // no exception thrown
  }

  public function testItPassesWithPathSplitAndJoin(): void {
    $automation = $this->createAutomation([
      'root' => ['a1', 'a2'],
      'a1' => ['b'],
      'a2' => ['b'],
      'b' => [],
    ]);

    (new AutomationWalker())->walk($automation, [new NoCycleRule()]);
    // no exception thrown
  }
}
