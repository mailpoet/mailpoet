<?php declare(strict_types=1);

namespace MailPoet\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowTemplate;
use MailPoet\Automation\Engine\Workflows\Trigger;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SegmentSubscribedTrigger;
use MailPoet\Util\Security;
use MailPoet\Validator\Schema\ObjectSchema;

class WorkflowBuilder {

  public function createFromSequence(string $name, array $sequence, array $sequenceArgs = []) : Workflow {
    $steps = [];
    $nextStep = null;
    foreach (array_reverse($sequence) as $index => $stepClass) {
      $step = new Step(
        $this->uniqueId(),
        in_array(Trigger::class, (array) class_implements($stepClass)) ? Step::TYPE_TRIGGER : Step::TYPE_ACTION,
        $stepClass::getKey(),
        $nextStep,
        array_reverse($sequenceArgs)[$index] ?? []
      );
      $nextStep = $step->getId();
      $steps[] = $step;
    }
    $steps = array_reverse($steps);
    return new Workflow(
      $name,
      $steps
    );
  }

  private function uniqueId(): string {
    return Security::generateRandomString(16);
  }
}
