<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;

class TriggerNeedsNextStepsRuleTest extends AutomationRuleTest {
  public function testItPassesWhenActionFollows(): void {
    $steps = [
      'root' => $this->createStep('root', Step::TYPE_ROOT, ['t']),
      't' => $this->createStep('t', Step::TYPE_TRIGGER, ['a']),
      'a' => $this->createStep('a', Step::TYPE_ACTION, []),
    ];
    $automation = $this->make(Automation::class, ['getSteps' => $steps, 'getStep' => function($id) use ($steps) { return $steps[$id] ?? null;

    }]);
    $automation->setStatus(Automation::STATUS_ACTIVE);

    (new AutomationWalker())->walk($automation, [new TriggerNeedsToBeFollowedByActionRule()]);
    //no exception thrown.
  }

  public function testItFailsWhenNoActionIsFollowed(): void {
    $steps = [
      'root' => $this->createStep('root', Step::TYPE_ROOT, ['t']),
      't' => $this->createStep('t', Step::TYPE_TRIGGER, []),
    ];
    $automation = $this->make(Automation::class, ['getSteps' => $steps, 'getStep' => function($id) use ($steps) { return $steps[$id] ?? null;

    }]);
    $automation->setStatus(Automation::STATUS_ACTIVE);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: A trigger needs to be followed by an action.');
    (new AutomationWalker())->walk($automation, [new TriggerNeedsToBeFollowedByActionRule()]);
  }

  public function testItFailsWhenFollowedByAStepNotBeingAnAction(): void {
    $steps = [
      'root' => $this->createStep('root', Step::TYPE_ROOT, ['t1']),
      't1' => $this->createStep('t1', Step::TYPE_TRIGGER, ['a', 't2']),
      'a' => $this->createStep('a', Step::TYPE_ACTION, []),
      't2' => $this->createStep('t2', Step::TYPE_TRIGGER, ['a']),
    ];
    $automation = $this->make(Automation::class, ['getSteps' => $steps, 'getStep' => function($id) use ($steps) { return $steps[$id] ?? null;

    }]);
    $automation->setStatus(Automation::STATUS_ACTIVE);


    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: A trigger needs to be followed by an action.');
    (new AutomationWalker())->walk($automation, [new TriggerNeedsToBeFollowedByActionRule()]);
  }
}
