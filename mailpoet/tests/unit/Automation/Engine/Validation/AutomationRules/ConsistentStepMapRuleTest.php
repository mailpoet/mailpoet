<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;

class ConsistentStepMapRuleTest extends AutomationRuleTest {
  public function testItDetectsWrongKeyValuePair(): void {
    $automation = $this->make(Automation::class, ['getSteps' => [
      'root' => $this->createStep('a'),
    ]]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Invalid automation structure: Step with ID 'a' stored under a mismatched index 'root'.");
    (new AutomationWalker())->walk($automation, [new ConsistentStepMapRule()]);
  }

  public function testItPassesWithCorrectKeyValuePair(): void {
    $automation = $this->make(Automation::class, ['getSteps' => [
      'root' => $this->createStep('root'),
    ]]);

    (new AutomationWalker())->walk($automation, [new ConsistentStepMapRule()]);
    // no exception thrown
  }
}
