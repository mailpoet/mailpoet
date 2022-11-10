<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;

class NoDuplicateEdgesTest extends AutomationRuleTest {
  public function testItDetectsDuplicateEdges(): void {
    $automation = $this->createAutomation([
      'root' => ['a', 'a'],
      'a' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Duplicate next step definition found');
    (new AutomationWalker())->walk($automation, [new NoDuplicateEdgesRule()]);
  }

  public function testItPassesWithSingleEdges(): void {
    $automation = $this->createAutomation([
      'root' => ['a'],
      'a' => ['b'],
      'b' => ['c'],
      'c' => [],
    ]);

    (new AutomationWalker())->walk($automation, [new NoDuplicateEdgesRule()]);
    // no exception thrown
  }
}
