<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;

class TriggersUnderRootRuleTest extends AutomationRuleTest {
  public function testItDetectsTriggersNotUnderRoot(): void {
    $automation = $this->make(Automation::class, ['getSteps' => [
      'root' => $this->createStep('root', Step::TYPE_ROOT, ['t', 'a']),
      'a' => $this->createStep('a', Step::TYPE_ACTION, ['t']),
      't' => $this->createStep('t', Step::TYPE_TRIGGER, []),
    ]]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Trigger must be a direct descendant of automation root');
    (new AutomationWalker())->walk($automation, [new TriggersUnderRootRule()]);
  }

  public function testItPassesWithTriggersUnderRoot(): void {
    $automation = $this->make(Automation::class, ['getSteps' => [
      'root' => $this->createStep('root', Step::TYPE_ROOT, ['t']),
      't' => $this->createStep('t', Step::TYPE_TRIGGER, []),
    ]]);

    (new AutomationWalker())->walk($automation, [new TriggersUnderRootRule()]);
    // no exception thrown
  }
}
