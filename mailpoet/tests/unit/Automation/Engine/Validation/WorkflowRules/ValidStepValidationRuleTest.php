<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

require_once __DIR__ . '/WorkflowRuleTest.php';

use Codeception\Stub\Expected;
use MailPoet\Automation\Engine\Control\RootStep;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Integration\Action;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;

class ValidStepValidationRuleTest extends WorkflowRuleTest {
  public function testItRunsStepValidation(): void {
    $workflow = $this->getWorkflow();
    $registry = $this->make(Registry::class, [
      'steps' => [
        'core:root' => $this->make(RootStep::class, [
          'validate' => Expected::once(function (StepValidationArgs $args) use ($workflow) {
            $this->assertSame($workflow, $args->getWorkflow());
            $this->assertSame($workflow->getStep('root'), $args->getStep());
            $this->assertSame([], $args->getSubjects());
          }),
        ]),
      ],
    ]);

    $rule = new ValidStepValidationRule($registry);
    (new WorkflowWalker())->walk($workflow, [$rule]);
  }

  public function testItCollectsAvailableSubjects(): void {
    $subjectA = $this->makeEmpty(Subject::class, ['getKey' => 'test:subject-a']);
    $subjectB = $this->makeEmpty(Subject::class, ['getKey' => 'test:subject-b']);

    $registry = $this->make(Registry::class, [
      'steps' => [
        'core:root' => $this->make(RootStep::class, [
          'validate' => Expected::once(function (StepValidationArgs $args) {
            $this->assertSame([], $args->getSubjects());
          }),
        ])
      ]
    ]);
    $registry->addTrigger(
      $this->makeEmpty(Trigger::class, [
        'getKey' => 'test:trigger',
        'getSubjectKeys' => ['test:subject-a', 'test:subject-b'],
        'validate' => Expected::once(function (StepValidationArgs $args) {
          $this->assertSame([], $args->getSubjects());
        }),
      ])
    );
    $registry->addAction(
      $this->makeEmpty(Action::class, [
        'getKey' => 'test:action',
        'validate' => Expected::once(function (StepValidationArgs $args) use ($subjectA, $subjectB) {
          $this->assertSame([$subjectA, $subjectB], $args->getSubjects());
        }),
      ])
    );

    $registry->addSubject($subjectA);
    $registry->addSubject($subjectB);

    $workflow = $this->make(Workflow::class, [
      'getId' => 1,
      'steps' => [
        'root' => new Step('root', 'root', 'core:root', [], [new NextStep('t')]),
        't' => new Step('t', 'trigger', 'test:trigger', [], [new NextStep('a')]),
        'a' => new Step('a', 'action', 'test:action', [], []),
      ],
    ]);

    $rule = new ValidStepValidationRule($registry);
    (new WorkflowWalker())->walk($workflow, [$rule]);
  }

  public function testItSkipsArgsValidationForNonExistentStep(): void {
    $registry = $this->make(Registry::class);
    $rule = new ValidStepValidationRule($registry);
    $workflow = $this->getWorkflow();
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
