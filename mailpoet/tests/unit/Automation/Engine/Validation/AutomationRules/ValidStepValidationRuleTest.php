<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use Codeception\Stub\Expected;
use MailPoet\Automation\Engine\Control\RootStep;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Integration\Action;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;

class ValidStepValidationRuleTest extends AutomationRuleTest {
  public function testItRunsStepValidation(): void {
    $automation = $this->getAutomation();
    $registry = $this->make(Registry::class, [
      'steps' => [
        'core:root' => $this->make(RootStep::class, [
          'validate' => Expected::once(function (StepValidationArgs $args) use ($automation) {
            $this->assertSame($automation, $args->getAutomation());
            $this->assertSame($automation->getStep('root'), $args->getStep());
            $this->assertSame([], $args->getSubjects());
          }),
        ]),
      ],
    ]);

    $rule = new ValidStepValidationRule($registry);
    (new AutomationWalker())->walk($automation, [$rule]);
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

    $automation = $this->make(Automation::class, [
      'getId' => 1,
      'steps' => [
        'root' => new Step('root', 'root', 'core:root', [], [new NextStep('t')]),
        't' => new Step('t', 'trigger', 'test:trigger', [], [new NextStep('a')]),
        'a' => new Step('a', 'action', 'test:action', [], []),
      ],
    ]);

    $rule = new ValidStepValidationRule($registry);
    (new AutomationWalker())->walk($automation, [$rule]);
  }

  public function testItSkipsArgsValidationForNonExistentStep(): void {
    $registry = $this->make(Registry::class);
    $rule = new ValidStepValidationRule($registry);
    $automation = $this->getAutomation();
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
