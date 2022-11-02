<?php

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;

require_once __DIR__ . '/WorkflowRuleTest.php';

class AtLeastOnTriggerTest extends WorkflowRuleTest
{
  public function testItPassesWhenTriggerExists(): void {
    $steps = [
      'root' => $this->createStep('root', Step::TYPE_ROOT, ['t']),
      't' => $this->createStep('t', Step::TYPE_TRIGGER),
    ];
    $workflow = $this->make(Workflow::class, ['getSteps' => $steps, 'getStep' => function($id) use ($steps) { return $steps[$id]??null; }]);

    (new WorkflowWalker())->walk($workflow, [new AtLeastOneTriggerRule()]);
    //no exception thrown.
  }

  public function testItFailsWhenNoTriggerExists(): void {
    $steps = [
      'root' => $this->createStep('root', Step::TYPE_ROOT)
    ];
    $workflow = $this->make(Workflow::class, ['getSteps' => $steps, 'getStep' => function($id) use ($steps) { return $steps[$id]??null; }]);


    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: There must be at least one trigger in the automation.');
    (new WorkflowWalker())->walk($workflow, [new AtLeastOneTriggerRule()]);
  }
}
