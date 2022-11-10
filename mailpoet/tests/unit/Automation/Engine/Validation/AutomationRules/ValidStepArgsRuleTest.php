<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use Codeception\Stub\Expected;
use MailPoet\Automation\Engine\Control\RootStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;
use MailPoet\Validator\Validator;

class ValidStepArgsRuleTest extends AutomationRuleTest {
  public function testItRunsArgsValidation(): void {
    $registry = $this->make(Registry::class, [
      'steps' => ['core:root' => new RootStep()],
    ]);

    $validator = $this->make(Validator::class, [
      'validate' => Expected::once(),
    ]);

    $rule = new ValidStepArgsRule($registry, $validator);
    $automation = $this->getAutomation();
    (new AutomationWalker())->walk($automation, [$rule]);
  }

  public function testItSkipsArgsValidationForNonExistentStep(): void {
    $registry = $this->make(Registry::class);
    $validator = $this->make(Validator::class, [
      'validate' => Expected::never(),
    ]);

    $rule = new ValidStepArgsRule($registry, $validator);
    $automation = $this->getAutomation();
    (new AutomationWalker())->walk($automation, [$rule]);
  }

  private function getAutomation(): Automation {
    return $this->make(Automation::class, [
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], []),
      ],
    ]);
  }
}
