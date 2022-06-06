<?php declare(strict_types=1);

namespace MailPoet\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\Automation\Integrations\Core\Actions\WaitAction;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendWelcomeEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SegmentSubscribedTrigger;
use MailPoet\Util\Security;

class Templates {

  /** @var WaitAction */
  private $waitAction;

  /** @var SegmentSubscribedTrigger */
  private $segmentSubscribedTrigger;

  /** @var SendWelcomeEmailAction */
  private $sendWelcomeEmailAction;

  public function __construct(
    SegmentSubscribedTrigger $segmentSubscribedTrigger,
    SendWelcomeEmailAction $sendWelcomeEmailAction,
    WaitAction $waitAction
  ) {
    $this->waitAction = $waitAction;
    $this->segmentSubscribedTrigger = $segmentSubscribedTrigger;
    $this->sendWelcomeEmailAction = $sendWelcomeEmailAction;
  }

  public function delayedEmailAfterSignup(string $name): Workflow {
    $triggerStep = $this->segmentSubscribedTriggerStep();

    $waitStep = $this->waitStep(60 * 60);
    $triggerStep->setNextStepId($waitStep->getId());

    $sendEmailStep = $this->sendEmailActionStep();
    $waitStep->setNextStepId($sendEmailStep->getId());
    
    $steps = [
      $triggerStep,
      $waitStep,
      $sendEmailStep,
    ];

    return new Workflow($name, $steps);
  }

  private function waitStep(int $seconds): Step {
    return new Step($this->uniqueId(), Step::TYPE_ACTION, $this->waitAction->getKey(), null, [
      'seconds' => $seconds,
    ]);
  }

  private function segmentSubscribedTriggerStep(?int $segmentId = null): Step {
    return new Step($this->uniqueId(), Step::TYPE_TRIGGER, $this->segmentSubscribedTrigger->getKey(), null, [
      'segment_id' => $segmentId,
    ]);
  }

  private function sendEmailActionStep(?int $newsletterId = null): Step {
    return new Step($this->uniqueId(), Step::TYPE_ACTION, $this->sendWelcomeEmailAction->getKey(), null, [
      'welcomeEmailId' => $newsletterId
    ]);
  }

  private function uniqueId(): string {
    return Security::generateRandomString(16);
  }
}
