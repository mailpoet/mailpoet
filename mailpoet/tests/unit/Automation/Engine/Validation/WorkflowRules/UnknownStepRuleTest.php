<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

require_once __DIR__ . '/WorkflowRuleTest.php';

use MailPoet\Automation\Engine\Control\RootStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;

class UnknownStepRuleTest extends WorkflowRuleTest {
  public function testItDetectsModificationWithoutExistingWorkflow(): void {
    $workflow = $this->getWorkflow();
    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Modification of step 'core:root' of type 'root' with ID 'root' is not supported when the related plugin is not active.");
    (new WorkflowWalker())->walk($workflow, [$this->getRule()]);
  }

  public function testItDetectsAddedStep(): void {
    $workflow = $this->getWorkflow();
    $existingWorkflow = $this->make(Workflow::class, [
      'getId' => 1,
      'getSteps' => [],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Modification of step 'core:root' of type 'root' with ID 'root' is not supported when the related plugin is not active.");
    (new WorkflowWalker())->walk($workflow, [$this->getRule($existingWorkflow)]);
  }

  public function testItDetectsChangedStep(): void {
    $workflow = $this->getWorkflow();
    $existingWorkflow = $this->make(Workflow::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => $this->createStep('root', Step::TYPE_ROOT, ['next-step-id']),
      ],
    ]);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage("Modification of step 'core:root' of type 'root' with ID 'root' is not supported when the related plugin is not active.");
    (new WorkflowWalker())->walk($workflow, [$this->getRule($existingWorkflow)]);
  }


  public function testItPassesWithDeletedStep(): void {
    $workflow = $this->getWorkflow();
    $existingWorkflow = $this->make(Workflow::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], []),
        'abc' => $this->createStep('abc', Step::TYPE_TRIGGER, []),
      ],
    ]);
    (new WorkflowWalker())->walk($workflow, [$this->getRule($existingWorkflow)]);
  }

  public function testItPassesWithoutChanges(): void {
    $workflow = $this->getWorkflow();
    (new WorkflowWalker())->walk($workflow, [$this->getRule($workflow)]);
  }

  public function testItPassesWithExistingRegistryStep(): void {
    $workflow = $this->make(Workflow::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], []),
      ],
    ]);

    $existingWorkflow = $this->make(Workflow::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', ['key' => 'value'], []),
      ],
    ]);
    (new WorkflowWalker())->walk($workflow, [$this->getRule($existingWorkflow, [new RootStep()])]);
  }

  private function getRule(Workflow $existingWorkflow = null, array $steps = []): UnknownStepRule {
    $stepMap = [];
    foreach ($steps as $step) {
      $stepMap[$step->getKey()] = $step;
    }
    $registry = $this->make(Registry::class, [
      'steps' => $stepMap,
    ]);
    $storage = $this->make(WorkflowStorage::class, [
      'getWorkflow' => $existingWorkflow,
    ]);
    return new UnknownStepRule($registry, $storage);
  }

  private function getWorkflow(): Workflow {
    return $this->make(Workflow::class, [
      'getId' => 1,
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], []),
      ],
    ]);
  }
}
