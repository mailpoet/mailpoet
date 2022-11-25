<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use MailPoet\Automation\Engine\Control\RootStep;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;

class UnknownStepRuleTest extends AutomationRuleTest {
  public function testItDetectsModificationWithoutExistingAutomation(): void {
    $automation = $this->getAutomation();
    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Modification of step 'core:root' of type 'root' with ID 'root' is not supported when the related plugin is not active.");
    (new AutomationWalker())->walk($automation, [$this->getRule()]);
  }

  public function testItDetectsAddedStep(): void {
    $automation = $this->getAutomation();
    $existingAutomation = $this->make(Automation::class, [
      'getId' => 1,
      'getSteps' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Modification of step 'core:root' of type 'root' with ID 'root' is not supported when the related plugin is not active.");
    (new AutomationWalker())->walk($automation, [$this->getRule($existingAutomation)]);
  }

  public function testItDetectsChangedStep(): void {
    $automation = $this->getAutomation();
    $existingAutomation = $this->make(Automation::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => $this->createStep('root', Step::TYPE_ROOT, ['next-step-id']),
      ],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Modification of step 'core:root' of type 'root' with ID 'root' is not supported when the related plugin is not active.");
    (new AutomationWalker())->walk($automation, [$this->getRule($existingAutomation)]);
  }

  public function testItPassesWithDeletedStep(): void {
    $automation = $this->getAutomation();
    $existingAutomation = $this->make(Automation::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], []),
        'abc' => $this->createStep('abc', Step::TYPE_TRIGGER, []),
      ],
    ]);
    (new AutomationWalker())->walk($automation, [$this->getRule($existingAutomation)]);
  }

  public function testItPassesWithoutChanges(): void {
    $automation = $this->getAutomation();
    (new AutomationWalker())->walk($automation, [$this->getRule($automation)]);
  }

  public function testItPassesWithExistingRegistryStep(): void {
    $automation = $this->make(Automation::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], []),
      ],
    ]);

    $existingAutomation = $this->make(Automation::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', ['key' => 'value'], []),
      ],
    ]);
    (new AutomationWalker())->walk($automation, [$this->getRule($existingAutomation, [new RootStep()])]);
  }

  private function getRule(Automation $existingAutomation = null, array $steps = []): UnknownStepRule {
    $stepMap = [];
    foreach ($steps as $step) {
      $stepMap[$step->getKey()] = $step;
    }
    $registry = $this->make(Registry::class, [
      'steps' => $stepMap,
    ]);
    $storage = $this->make(AutomationStorage::class, [
      'getAutomation' => $existingAutomation,
    ]);
    return new UnknownStepRule($registry, $storage);
  }

  private function getAutomation(): Automation {
    return $this->make(Automation::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], []),
      ],
    ]);
  }
}
