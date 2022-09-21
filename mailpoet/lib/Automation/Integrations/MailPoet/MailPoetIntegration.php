<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet;

use MailPoet\Automation\Engine\Integration;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Automation\Integrations\MailPoet\Triggers\UserRegistrationTrigger;

class MailPoetIntegration implements Integration {
  /** @var SegmentSubject */
  private $segmentSubject;

  /** @var SubscriberSubject */
  private $subscriberSubject;

  /** @var SomeoneSubscribesTrigger */
  private $someoneSubscribesTrigger;

  /** @var UserRegistrationTrigger  */
  private $userRegistrationTrigger;

  /** @var SendEmailAction */
  private $sendEmailAction;

  public function __construct(
    SegmentSubject $segmentSubject,
    SubscriberSubject $subscriberSubject,
    SomeoneSubscribesTrigger $someoneSubscribesTrigger,
    UserRegistrationTrigger $userRegistrationTrigger,
    SendEmailAction $sendEmailAction
  ) {
    $this->segmentSubject = $segmentSubject;
    $this->subscriberSubject = $subscriberSubject;
    $this->someoneSubscribesTrigger = $someoneSubscribesTrigger;
    $this->userRegistrationTrigger = $userRegistrationTrigger;
    $this->sendEmailAction = $sendEmailAction;
  }

  public function register(Registry $registry): void {
    $registry->addSubject($this->segmentSubject);
    $registry->addSubject($this->subscriberSubject);
    $registry->addTrigger($this->someoneSubscribesTrigger);
    $registry->addTrigger($this->userRegistrationTrigger);
    $registry->addAction($this->sendEmailAction);

    // sync step args (subject, preheader, etc.) to email settings
    $registry->onBeforeWorkflowStepSave(
      [$this->sendEmailAction, 'saveEmailSettings'],
      $this->sendEmailAction->getKey()
    );
  }
}
