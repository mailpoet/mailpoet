<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use Codeception\Stub\Expected;
use Exception;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationNodeVisitor;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;

class ValidStepRuleTest extends AutomationRuleTest {
  public function testItRunsStepValidation(): void {
    $automation = $this->getAutomation();
    $automation->setStatus(Automation::STATUS_ACTIVE);

    $rule = new ValidStepRule([
      $this->makeEmpty(AutomationNodeVisitor::class, [
        'initialize' => Expected::once(),
        'visitNode' => Expected::once(),
        'complete' => Expected::once(),
      ])
    ]);
    (new AutomationWalker())->walk($automation, [$rule]);
  }

  public function testItCollectsRecognizedErrors(): void {
    $automation = $this->getAutomation();
    $automation->setStatus(Automation::STATUS_ACTIVE);

    $rule = new ValidStepRule([
      $this->makeEmpty(AutomationNodeVisitor::class, [
        'visitNode' => Expected::once(function () {
          throw UnexpectedValueException::create()->withMessage('Test error');
        }),
      ]),
    ]);

    try {
      (new AutomationWalker())->walk($automation, [$rule]);
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
    $automation = $this->getAutomation();
    $automation->setStatus(Automation::STATUS_ACTIVE);

    $rule = new ValidStepRule([
      $this->makeEmpty(AutomationNodeVisitor::class, [
        'visitNode' => Expected::once(function () {
          throw new Exception(' Unknown test error');
        }),
      ]),
    ]);

    try {
      (new AutomationWalker())->walk($automation, [$rule]);
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

  public function testItValidatesOnlyActiveAutomations(): void {
    $automation = $this->getAutomation();
    $automation->setStatus(Automation::STATUS_DRAFT);
    $rule = new ValidStepRule([
      $this->makeEmpty(AutomationNodeVisitor::class, [
        'initialize' => Expected::never(),
        'visitNode' => Expected::never(),
        'complete' => Expected::never(),
      ])
    ]);
    (new AutomationWalker())->walk($automation, [$rule]);
  }

  private function getAutomation(): Automation {
    return $this->make(Automation::class, [
      'steps' => [
        'root' => new Step('root', 'root', 'core:root', [], []),
      ],
    ]);
  }
}
