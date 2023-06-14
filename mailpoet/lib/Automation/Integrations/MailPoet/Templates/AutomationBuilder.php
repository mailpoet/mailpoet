<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Util\Security;
use MailPoet\Validator\Schema\ObjectSchema;

class AutomationBuilder {

  /** @var Registry */
  private $registry;

  public function __construct(
    Registry $registry
  ) {
    $this->registry = $registry;
  }

  /**
   * @param string $name
   * @param array<
   *   array{
   *     key: string,
   *     args?: array<string, mixed>,
   *   }
   * > $sequence
   * @param array<string, mixed> $meta
   * @return Automation
   */
  public function createFromSequence(string $name, array $sequence, array $meta = []): Automation {
    $steps = [];
    $nextSteps = [];
    foreach (array_reverse($sequence) as $data) {
      $stepKey = $data['key'];
      $automationStep = $this->registry->getStep($stepKey);
      if (!$automationStep) {
        continue;
      }
      $args = array_merge($this->getDefaultArgs($automationStep->getArgsSchema()), $data['args'] ?? []);
      $step = new Step(
        $this->uniqueId(),
        in_array(Trigger::class, (array)class_implements($automationStep)) ? Step::TYPE_TRIGGER : Step::TYPE_ACTION,
        $stepKey,
        $args,
        $nextSteps
      );
      $nextSteps = [new NextStep($step->getId())];
      $steps[$step->getId()] = $step;
    }
    $steps['root'] = new Step('root', 'root', 'core:root', [], $nextSteps);
    $steps = array_reverse($steps);
    $automation = new Automation(
      $name,
      $steps,
      wp_get_current_user()
    );
    foreach ($meta as $key => $value) {
      $automation->setMeta($key, $value);
    }
    return $automation;
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
