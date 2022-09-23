<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Workflows\Trigger;
use MailPoet\Util\Security;
use MailPoet\Validator\Schema\ObjectSchema;

class WorkflowBuilder {

  /** @var Registry */
  private $registry;

  public function __construct(
    Registry $registry
  ) {
    $this->registry = $registry;
  }

  public function createFromSequence(string $name, array $sequence, array $sequenceArgs = []): Workflow {
    $steps = [];
    $nextSteps = [];
    foreach (array_reverse($sequence) as $index => $stepKey) {
      $workflowStep = $this->registry->getStep($stepKey);
      if (!$workflowStep) {
        continue;
      }
      $args = array_merge($this->getDefaultArgs($workflowStep->getArgsSchema()), array_reverse($sequenceArgs)[$index] ?? []);
      $step = new Step(
        $this->uniqueId(),
        in_array(Trigger::class, (array)class_implements($workflowStep)) ? Step::TYPE_TRIGGER : Step::TYPE_ACTION,
        $stepKey,
        $args,
        $nextSteps
      );
      $nextSteps = [new NextStep($step->getId())];
      $steps[$step->getId()] = $step;
    }
    $steps['root'] = new Step('root', 'root', 'core:root', [], $nextSteps);
    $steps = array_reverse($steps);
    return new Workflow(
      $name,
      $steps,
      wp_get_current_user()
    );
  }

  private function uniqueId(): string {
    return Security::generateRandomString(16);
  }

  private function getDefaultArgs(ObjectSchema $argsSchema): array {
    $args = [];
    foreach ($argsSchema->toArray()['properties'] ?? [] as $name => $schema) {
      if (array_key_exists('default', $schema)) {
        $args[$name] = $schema['default'];
      }
    }
    return $args;
  }
}
