<?php

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

require_once __DIR__ . '/WorkflowRuleTest.php';

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;

class TriggerNeedsNextStepsRuleTest extends WorkflowRuleTest
{
  public function testItPassesWhenActionFollows(): void {
    $steps = [
      'root' => $this->createStep('root', Step::TYPE_ROOT, ['t']),
      't' => $this->createStep('t', Step::TYPE_TRIGGER, ['a']),
      'a' => $this->createStep('a', Step::TYPE_ACTION, []),
    ];
    $workflow = $this->make(Workflow::class, ['getSteps' => $steps, 'getStep' => function($id) use ($steps) { return $steps[$id]??null; }]);

    (new WorkflowWalker())->walk($workflow, [new TriggerNeedsToBeFollowedByActionRule()]);
    //no exception thrown.
  }

  public function testItFailsWhenNoActionIsFollowed(): void {
    $steps = [
      'root' => $this->createStep('root', Step::TYPE_ROOT, ['t']),
      't' => $this->createStep('t', Step::TYPE_TRIGGER, []),
    ];
    $workflow = $this->make(Workflow::class, ['getSteps' => $steps, 'getStep' => function($id) use ($steps) { return $steps[$id]??null; }]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: A trigger needs to be followed by an action.');
    (new WorkflowWalker())->walk($workflow, [new TriggerNeedsToBeFollowedByActionRule()]);
  }

  public function testItFailsWhenFollowedByAStepNotBeingAnAction(): void {
    $steps = [
      'root' => $this->createStep('root', Step::TYPE_ROOT, ['t1']),
      't1' => $this->createStep('t1', Step::TYPE_TRIGGER, ['a', 't2']),
      'a' => $this->createStep('a', Step::TYPE_ACTION, []),
      't2' => $this->createStep('t2', Step::TYPE_TRIGGER, ['a']),
    ];
    $workflow = $this->make(Workflow::class, ['getSteps' => $steps, 'getStep' => function($id) use ($steps) { return $steps[$id]??null; }]);


    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: A trigger needs to be followed by an action.');
    (new WorkflowWalker())->walk($workflow, [new TriggerNeedsToBeFollowedByActionRule()]);
  }
}
