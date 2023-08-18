<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use MailPoet\Automation\Engine\Control\RootStep;
use MailPoet\Automation\Engine\Control\StepRunController;
use MailPoet\Automation\Engine\Control\SubjectTransformerHandler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Integration\Action;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;
use MailPoet\Validator\Schema\ObjectSchema;

class ValidStepOrderRuleTest extends AutomationRuleTest {
  public function testItDetectsMissingSubjects(): void {
    $registry = $this->make(Registry::class);
    $registry->addStep(new RootStep());
    $registry->addStep($this->getTrigger('test:trigger', ['subject-a']));
    $registry->addStep($this->getAction('test:action', ['subject-a', 'subject-b']));

    $automation = $this->make(Automation::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], [new NextStep('t')]),
        't' => new Step('t', 'trigger', 'test:trigger', [], [new NextStep('a')]),
        'a' => new Step('a', 'action', 'test:action', [], []),
      ],
    ]);

    $subjectTransformer = $this->createMock(SubjectTransformerHandler::class);
    $subjectTransformer->expects($this->any())->method('getSubjectKeysForAutomation')->willReturnCallback(function($a) use ($automation) {
      return $a === $automation ? ['subject-a'] : [];
    });
    $rule = new ValidStepOrderRule($registry, $subjectTransformer);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Step with ID 'a' is missing required subjects with keys: subject-b");
    (new AutomationWalker())->walk($automation, [$rule]);
  }

  public function testItDetectsMissingSubjectsInOneBranch(): void {
    $registry = $this->make(Registry::class);
    $registry->addStep(new RootStep());
    $registry->addStep($this->getTrigger('test:trigger-a', ['subject-a', 'subject-b']));
    $registry->addStep($this->getTrigger('test:trigger-b', ['subject-a']));
    $registry->addStep($this->getAction('test:action', ['subject-a', 'subject-b']));

    $automation = $this->make(Automation::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], [new NextStep('ta'), new NextStep('tb')]),
        'ta' => new Step('ta', 'trigger', 'test:trigger-a', [], [new NextStep('s')]),
        'tb' => new Step('tb', 'trigger', 'test:trigger-b', [], [new NextStep('s')]),
        's' => new Step('s', 'action', 'test:action', [], []),
      ],
    ]);

    $subjectTransformer = $this->createMock(SubjectTransformerHandler::class);
    $subjectTransformer->expects($this->any())->method('getSubjectKeysForAutomation')->willReturnCallback(function($a) use ($automation) {
      return $a === $automation ? ['subject-a'] : [];
    });

    $rule = new ValidStepOrderRule($registry, $subjectTransformer);


    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Step with ID 's' is missing required subjects with keys: subject-b");
    (new AutomationWalker())->walk($automation, [$rule]);
  }

  public function testItPassesForSimpleAutomation(): void {
    $registry = $this->make(Registry::class);
    $registry->addStep(new RootStep());
    $registry->addStep($this->getTrigger('test:trigger'));
    $registry->addStep($this->getAction('test:action'));

    $automation = $this->make(Automation::class, [
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], [new NextStep('t')]),
        't' => new Step('t', 'trigger', 'test:trigger', [], [new NextStep('a')]),
        'a' => new Step('a', 'action', 'test:action', [], []),
      ],
    ]);

    $subjectTransformer = $this->createMock(SubjectTransformerHandler::class);
    $subjectTransformer->expects($this->any())->method('getSubjectKeysForAutomation')->willReturnCallback(function($a) use ($automation) {
      return $a === $automation ? ['subject-a'] : [];
    });
    $rule = new ValidStepOrderRule($registry, $subjectTransformer);

    (new AutomationWalker())->walk($automation, [$rule]);
  }

  public function testItPassesForAutomationWithCorrectSubjects(): void {
    $registry = $this->make(Registry::class);
    $registry->addStep(new RootStep());
    $registry->addStep($this->getTrigger('test:trigger', ['subject-a', 'subject-b', 'subject-c']));
    $registry->addStep($this->getAction('test:action', ['subject-a', 'subject-b']));

    $automation = $this->make(Automation::class, [
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], [new NextStep('t')]),
        't' => new Step('t', 'trigger', 'test:trigger', [], [new NextStep('a')]),
        'a' => new Step('a', 'action', 'test:action', [], []),
      ],
    ]);

    $subjectTransformer = $this->createMock(SubjectTransformerHandler::class);
    $subjectTransformer->expects($this->any())->method('getSubjectKeysForAutomation')->willReturnCallback(function($a) use ($automation) {
      return $a === $automation ? ['subject-a', 'subject-b'] : [];
    });

    $rule = new ValidStepOrderRule($registry, $subjectTransformer);

    (new AutomationWalker())->walk($automation, [$rule]);
  }

  public function testItPassesForMultibranchAutomationWithCorrectSubjects(): void {
    $registry = $this->make(Registry::class);
    $registry->addStep(new RootStep());
    $registry->addStep($this->getTrigger('test:trigger-a', ['subject-a', 'subject-b', 'subject-c']));
    $registry->addStep($this->getTrigger('test:trigger-b', ['subject-a', 'subject-b']));
    $registry->addStep($this->getAction('test:action', ['subject-a', 'subject-b']));

    $automation = $this->make(Automation::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], [new NextStep('ta'), new NextStep('tb')]),
        'ta' => new Step('ta', 'trigger', 'test:trigger-a', [], [new NextStep('s')]),
        'tb' => new Step('tb', 'trigger', 'test:trigger-b', [], [new NextStep('s')]),
        's' => new Step('s', 'action', 'test:action', [], []),
      ],
    ]);

    $subjectTransformer = $this->createMock(SubjectTransformerHandler::class);
    $subjectTransformer->expects($this->any())->method('getSubjectKeysForAutomation')->willReturnCallback(function($a) use ($automation) {
      return $a === $automation ? ['subject-a', 'subject-b'] : [];
    });
    $rule = new ValidStepOrderRule($registry, $subjectTransformer);

    (new AutomationWalker())->walk($automation, [$rule]);
  }

  private function getTrigger(string $key, array $subjectKeys = []): Trigger {
    return new class($key, $subjectKeys) implements Trigger {
      /** @var string */
      private $key;

      /** @var array */
      private $subjectKeys;

      public function __construct(
        string $key,
        array $subjectKeys
      ) {
        $this->key = $key;
        $this->subjectKeys = $subjectKeys;
      }

      public function getKey(): string {
        return $this->key;
      }

      public function getName(): string {
        return 'Test trigger';
      }

      public function getArgsSchema(): ObjectSchema {
        return new ObjectSchema();
      }

      public function getSubjectKeys(): array {
        return $this->subjectKeys;
      }

      public function validate(StepValidationArgs $args): void {
      }

      public function registerHooks(): void {
      }

      public function isTriggeredBy(StepRunArgs $args): bool {
        return true;
      }
    };
  }

  private function getAction(string $key, array $subjectKeys = []): Action {
    return new class($key, $subjectKeys) implements Action {
      /** @var string */
      private $key;

      /** @var array */
      private $subjectKeys;

      public function __construct(
        string $key,
        array $subjectKeys
      ) {
        $this->key = $key;
        $this->subjectKeys = $subjectKeys;
      }

      public function run(StepRunArgs $args, StepRunController $controller): void {
      }

      public function getKey(): string {
        return $this->key;
      }

      public function getName(): string {
        return 'Test action';
      }

      public function getArgsSchema(): ObjectSchema {
        return new ObjectSchema();
      }

      public function getSubjectKeys(): array {
        return $this->subjectKeys;
      }

      public function validate(StepValidationArgs $args): void {
      }
    };
  }
}
