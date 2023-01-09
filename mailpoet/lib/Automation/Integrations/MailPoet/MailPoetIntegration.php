<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet;

use MailPoet\Automation\Engine\Integration;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Hooks\AutomationEditorLoadingHooks;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Automation\Integrations\MailPoet\Triggers\UserRegistrationTrigger;

class MailPoetIntegration implements Integration {
  /** @var ContextFactory */
  private $contextFactory;

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

  private $automationEditorLoadingHooks;

  public function __construct(
    ContextFactory $contextFactory,
    SegmentSubject $segmentSubject,
    SubscriberSubject $subscriberSubject,
    SomeoneSubscribesTrigger $someoneSubscribesTrigger,
    UserRegistrationTrigger $userRegistrationTrigger,
    SendEmailAction $sendEmailAction,
    AutomationEditorLoadingHooks $automationEditorLoadingHooks
  ) {
    $this->contextFactory = $contextFactory;
    $this->segmentSubject = $segmentSubject;
    $this->subscriberSubject = $subscriberSubject;
    $this->someoneSubscribesTrigger = $someoneSubscribesTrigger;
    $this->userRegistrationTrigger = $userRegistrationTrigger;
    $this->sendEmailAction = $sendEmailAction;
    $this->automationEditorLoadingHooks = $automationEditorLoadingHooks;
  }

  public function register(Registry $registry): void {
    $registry->addContextFactory('mailpoet', function () {
      return $this->contextFactory->getContextData();
    });

    $registry->addSubject($this->segmentSubject);
    $registry->addSubject($this->subscriberSubject);
    $registry->addTrigger($this->someoneSubscribesTrigger);
    $registry->addTrigger($this->userRegistrationTrigger);
    $registry->addAction($this->sendEmailAction);

    // sync step args (subject, preheader, etc.) to email settings
    $registry->onBeforeAutomationStepSave(
      [$this->sendEmailAction, 'saveEmailSettings'],
      $this->sendEmailAction->getKey()
    );

    $this->automationEditorLoadingHooks->init();
  }
}
