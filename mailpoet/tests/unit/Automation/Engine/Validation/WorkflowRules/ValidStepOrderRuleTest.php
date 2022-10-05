<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

require_once __DIR__ . '/WorkflowRuleTest.php';

use MailPoet\Automation\Engine\Control\RootStep;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Integration\Action;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;
use MailPoet\Validator\Schema\ObjectSchema;

class ValidStepOrderRuleTest extends WorkflowRuleTest {
  public function testItDetectsMissingSubjects(): void {
    $registry = $this->make(Registry::class);
    $registry->addStep(new RootStep());
    $registry->addStep($this->getTrigger('test:trigger', ['subject-a']));
    $registry->addStep($this->getAction('test:action', ['subject-a', 'subject-b']));
    $rule = new ValidStepOrderRule($registry);

    $workflow = $this->make(Workflow::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], [new NextStep('t')]),
        't' => new Step('t', 'trigger', 'test:trigger', [], [new NextStep('a')]),
        'a' => new Step('a', 'action', 'test:action', [], []),
      ],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Step with ID 'a' is missing required subjects with keys: subject-b");
    (new WorkflowWalker())->walk($workflow, [$rule]);
  }

  public function testItDetectsMissingSubjectsInOneBranch(): void {
    $registry = $this->make(Registry::class);
    $registry->addStep(new RootStep());
    $registry->addStep($this->getTrigger('test:trigger-a', ['subject-a', 'subject-b']));
    $registry->addStep($this->getTrigger('test:trigger-b', ['subject-a']));
    $registry->addStep($this->getAction('test:action', ['subject-a', 'subject-b']));
    $rule = new ValidStepOrderRule($registry);

    $workflow = $this->make(Workflow::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], [new NextStep('ta'), new NextStep('tb')]),
        'ta' => new Step('ta', 'trigger', 'test:trigger-a', [], [new NextStep('s')]),
        'tb' => new Step('tb', 'trigger', 'test:trigger-b', [], [new NextStep('s')]),
        's' => new Step('s', 'action', 'test:action', [], []),
      ],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Step with ID 's' is missing required subjects with keys: subject-b");
    (new WorkflowWalker())->walk($workflow, [$rule]);
  }

  public function testItPassesForSimpleWorkflow(): void {
    $registry = $this->make(Registry::class);
    $registry->addStep(new RootStep());
    $registry->addStep($this->getTrigger('test:trigger'));
    $registry->addStep($this->getAction('test:action'));
    $rule = new ValidStepOrderRule($registry);

    $workflow = $this->make(Workflow::class, [
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], [new NextStep('t')]),
        't' => new Step('t', 'trigger', 'test:trigger', [], [new NextStep('a')]),
        'a' => new Step('a', 'action', 'test:action', [], []),
      ],
    ]);
    (new WorkflowWalker())->walk($workflow, [$rule]);
  }

  public function testItPassesForWorkflowWithCorrectSubjects(): void {
    $registry = $this->make(Registry::class);
    $registry->addStep(new RootStep());
    $registry->addStep($this->getTrigger('test:trigger', ['subject-a', 'subject-b', 'subject-c']));
    $registry->addStep($this->getAction('test:action', ['subject-a', 'subject-b']));
    $rule = new ValidStepOrderRule($registry);

    $workflow = $this->make(Workflow::class, [
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], [new NextStep('t')]),
        't' => new Step('t', 'trigger', 'test:trigger', [], [new NextStep('a')]),
        'a' => new Step('a', 'action', 'test:action', [], []),
      ],
    ]);
    (new WorkflowWalker())->walk($workflow, [$rule]);
  }

  public function testItPassesForMultibranchWorkflowWithCorrectSubjects(): void {
    $registry = $this->make(Registry::class);
    $registry->addStep(new RootStep());
    $registry->addStep($this->getTrigger('test:trigger-a', ['subject-a', 'subject-b', 'subject-c']));
    $registry->addStep($this->getTrigger('test:trigger-b', ['subject-a', 'subject-b']));
    $registry->addStep($this->getAction('test:action', ['subject-a', 'subject-b']));
    $rule = new ValidStepOrderRule($registry);

    $workflow = $this->make(Workflow::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], [new NextStep('ta'), new NextStep('tb')]),
        'ta' => new Step('ta', 'trigger', 'test:trigger-a', [], [new NextStep('s')]),
        'tb' => new Step('tb', 'trigger', 'test:trigger-b', [], [new NextStep('s')]),
        's' => new Step('s', 'action', 'test:action', [], []),
      ],
    ]);
    (new WorkflowWalker())->walk($workflow, [$rule]);
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

      public function run(StepRunArgs $args): void {
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
