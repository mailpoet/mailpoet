<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

require_once __DIR__ . '/WorkflowRuleTest.php';

use Codeception\Stub\Expected;
use Exception;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;

class ValidStepRuleTest extends WorkflowRuleTest {
  public function testItRunsStepValidation(): void {
    $workflow = $this->getWorkflow();
    $workflow->setStatus(Workflow::STATUS_ACTIVE);

    $rule = new ValidStepRule([
      $this->makeEmpty(WorkflowNodeVisitor::class, [
        'initialize' => Expected::once(),
        'visitNode' => Expected::once(),
        'complete' => Expected::once(),
      ])
    ]);
    (new WorkflowWalker())->walk($workflow, [$rule]);
  }

  public function testItCollectsRecognizedErrors(): void {
    $workflow = $this->getWorkflow();
    $workflow->setStatus(Workflow::STATUS_ACTIVE);

    $rule = new ValidStepRule([
      $this->makeEmpty(WorkflowNodeVisitor::class, [
        'visitNode' => Expected::once(function () {
          throw UnexpectedValueException::create()->withMessage('Test error');
        }),
      ]),
    ]);

    try {
      (new WorkflowWalker())->walk($workflow, [$rule]);
    } catch (UnexpectedValueException $e) {
      $errors = $e->getErrors();
      $this->assertSame(
        [
          'root' => [
            'step_id' => 'root',
            'message' => 'Test error',
            'fields' => []
          ],
        ],
        $e->getErrors()
      );
      return;
    }
    $this->fail(sprintf("Exception of class '%s' was not thrown.", UnexpectedValueException::class));
  }


  public function testItCollectsUnrecognizedErrorsWithAGenericMessage(): void {
    $workflow = $this->getWorkflow();
    $workflow->setStatus(Workflow::STATUS_ACTIVE);

    $rule = new ValidStepRule([
      $this->makeEmpty(WorkflowNodeVisitor::class, [
        'visitNode' => Expected::once(function () {
          throw new Exception(' Unknown test error');
        }),
      ]),
    ]);

    try {
      (new WorkflowWalker())->walk($workflow, [$rule]);
    } catch (UnexpectedValueException $e) {
      $this->assertSame(
        [
          'root' => [
            'step_id' => 'root',
            'message' => 'Unknown error.',
            'fields' => [],
          ],
        ],
        $e->getErrors()
      );
      return;
    }
    $this->fail(sprintf("Exception of class '%s' was not thrown.", UnexpectedValueException::class));
  }

  public function testItValidatesOnlyActiveWorkflows(): void {
    $workflow = $this->getWorkflow();
    $workflow->setStatus(Workflow::STATUS_DRAFT);
    $rule = new ValidStepRule([
      $this->makeEmpty(WorkflowNodeVisitor::class, [
        'initialize' => Expected::never(),
        'visitNode' => Expected::never(),
        'complete' => Expected::never(),
      ])
    ]);
    (new WorkflowWalker())->walk($workflow, [$rule]);
  }

  private function getWorkflow(): Workflow {
    return $this->make(Workflow::class, [
      'steps' => [
        'root' => new Step('root', 'root', 'core:root', [], []),
      ],
    ]);
  }
}
