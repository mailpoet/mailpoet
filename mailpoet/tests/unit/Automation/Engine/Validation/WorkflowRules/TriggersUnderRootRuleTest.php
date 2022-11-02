<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

require_once __DIR__ . '/WorkflowRuleTest.php';

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;

class TriggersUnderRootRuleTest extends WorkflowRuleTest {
  public function testItDetectsTriggersNotUnderRoot(): void {
    $workflow = $this->make(Workflow::class, ['getSteps' => [
      'root' => $this->createStep('root', Step::TYPE_ROOT, ['t', 'a']),
      'a' => $this->createStep('a', Step::TYPE_ACTION, ['t']),
      't' => $this->createStep('t', Step::TYPE_TRIGGER, []),
    ]]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Invalid automation structure: Trigger must be a direct descendant of automation root');
    (new WorkflowWalker())->walk($workflow, [new TriggersUnderRootRule()]);
  }

  public function testItPassesWithTriggersUnderRoot(): void {
    $workflow = $this->make(Workflow::class, ['getSteps' => [
      'root' => $this->createStep('root', Step::TYPE_ROOT, ['t']),
      't' => $this->createStep('t', Step::TYPE_TRIGGER, []),
    ]]);

    (new WorkflowWalker())->walk($workflow, [new TriggersUnderRootRule()]);
    // no exception thrown
  }
}
