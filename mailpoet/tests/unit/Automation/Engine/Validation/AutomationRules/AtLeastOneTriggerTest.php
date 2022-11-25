<?php

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;

require_once __DIR__ . '/AutomationRuleTest.php';

class AtLeastOneTriggerTest extends AutomationRuleTest {
  public function testItPassesWhenTriggerExists(): void {
    $steps = [
      'root' => $this->createStep('root', Step::TYPE_ROOT, ['t']),
      't' => $this->createStep('t', Step::TYPE_TRIGGER),
    ];
    $automation = $this->make(Automation::class, ['getSteps' => $steps, 'getStep' => function($id) use ($steps) { return $steps[$id] ?? null;

    }]);
    $automation->setStatus(Automation::STATUS_ACTIVE);

    (new AutomationWalker())->walk($automation, [new AtLeastOneTriggerRule()]);
    //no exception thrown.
  }

  public function testItFailsWhenNoTriggerExists(): void {
    $steps = [
      'root' => $this->createStep('root', Step::TYPE_ROOT),
    ];
    $automation = $this->make(Automation::class, ['getSteps' => $steps, 'getStep' => function($id) use ($steps) { return $steps[$id] ?? null;

    }]);
    $automation->setStatus(Automation::STATUS_ACTIVE);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: There must be at least one trigger in the automation.');
    (new AutomationWalker())->walk($automation, [new AtLeastOneTriggerRule()]);
  }
}
