<?php declare(strict_types=1);

namespace MailPoet\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SegmentSubscribedTrigger;
use MailPoet\Util\Security;

class WorkflowBuilder {

  /** @var DelayAction */
  private $delayAction;

  /** @var SegmentSubscribedTrigger */
  private $segmentSubscribedTrigger;

  /** @var SendEmailAction */
  private $sendEmailAction;

  public function __construct(
    SegmentSubscribedTrigger $segmentSubscribedTrigger,
    SendEmailAction $sendEmailAction,
    DelayAction $delayAction
  ) {
    $this->delayAction = $delayAction;
    $this->segmentSubscribedTrigger = $segmentSubscribedTrigger;
    $this->sendEmailAction = $sendEmailAction;
  }

  public function delayedEmailAfterSignupWorkflow(string $name): Workflow {
    $triggerStep = $this->segmentSubscribedTriggerStep();

    $delayStep = $this->delayStep(null, "HOURS");
    $triggerStep->setNextStepId($delayStep->getId());

    $sendEmailStep = $this->sendEmailActionStep();
    $delayStep->setNextStepId($sendEmailStep->getId());

    $steps = [
      $triggerStep,
      $delayStep,
      $sendEmailStep,
    ];

    return new Workflow($name, $steps);
  }

  public function welcomeEmailSequence(string $name): Workflow {
    $triggerStep = $this->segmentSubscribedTriggerStep();

    $firstDelayStep = $this->delayStep( null, "HOURS");
    $triggerStep->setNextStepId($firstDelayStep->getId());

    $sendFirstEmailStep = $this->sendEmailActionStep(1);
    $firstDelayStep->setNextStepId($sendFirstEmailStep->getId());

    $secondDelayStep = $this->delayStep( null,"HOURS");
    $sendFirstEmailStep->setNextStepId($secondDelayStep->getId());

    $sendSecondEmailStep = $this->sendEmailActionStep(2);
    $secondDelayStep->setNextStepId($sendSecondEmailStep->getId());

    $steps = [
      $triggerStep,
      $firstDelayStep,
      $sendFirstEmailStep,
      $secondDelayStep,
      $sendSecondEmailStep,
    ];

    return new Workflow($name, $steps);
  }

  private function delayStep(?int $delay, string $delayType): Step {
    return new Step($this->uniqueId(), Step::TYPE_ACTION, $this->delayAction->getKey(), null, [
      'delay' => $delay??"",
      'delay_type' => $delayType,
    ]);
  }

  private function segmentSubscribedTriggerStep(?int $segmentId = null): Step {
    return new Step($this->uniqueId(), Step::TYPE_TRIGGER, $this->segmentSubscribedTrigger->getKey(), null, [
      'segment_id' => $segmentId,
    ]);
  }

  private function sendEmailActionStep(?int $newsletterId = null): Step {
    return new Step($this->uniqueId(), Step::TYPE_ACTION, $this->sendEmailAction->getKey(), null, [
      'email_id' => $newsletterId
    ]);
  }

  private function uniqueId(): string {
    return Security::generateRandomString(16);
  }
}
