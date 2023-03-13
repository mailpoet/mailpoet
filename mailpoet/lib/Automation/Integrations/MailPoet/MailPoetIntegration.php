<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet;

use MailPoet\Automation\Engine\Integration;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Hooks\AutomationEditorLoadingHooks;
use MailPoet\Automation\Integrations\MailPoet\Hooks\CreateAutomationRunHook;
use MailPoet\Automation\Integrations\MailPoet\Subjects\CustomerSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\OrderStatusChangeSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\OrderSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\OrderStatusChangedTrigger;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Automation\Integrations\MailPoet\Triggers\UserRegistrationTrigger;

class MailPoetIntegration implements Integration {
  /** @var ContextFactory */
  private $contextFactory;

  /** @var SegmentSubject */
  private $segmentSubject;

  /** @var SubscriberSubject */
  private $subscriberSubject;

  /** @var OrderSubject */
  private $orderSubject;

  /** @var OrderStatusChangeSubject */
  private $orderStatusChangeSubject;

  /** @var CustomerSubject */
  private $customerSubject;

  /** @var SomeoneSubscribesTrigger */
  private $someoneSubscribesTrigger;

  /** @var UserRegistrationTrigger  */
  private $userRegistrationTrigger;

  /** @var OrderStatusChangedTrigger  */
  private $orderStatusChangedTrigger;

  /** @var SendEmailAction */
  private $sendEmailAction;

  /** @var AutomationEditorLoadingHooks  */
  private $automationEditorLoadingHooks;

  /** @var CreateAutomationRunHook */
  private $createAutomationRunHook;

  public function __construct(
    ContextFactory $contextFactory,
    SegmentSubject $segmentSubject,
    SubscriberSubject $subscriberSubject,
    OrderSubject $orderSubject,
    OrderStatusChangeSubject $orderStatusChangeSubject,
    CustomerSubject $customerSubject,
    SomeoneSubscribesTrigger $someoneSubscribesTrigger,
    UserRegistrationTrigger $userRegistrationTrigger,
    OrderStatusChangedTrigger $orderStatusChangedTrigger,
    SendEmailAction $sendEmailAction,
    AutomationEditorLoadingHooks $automationEditorLoadingHooks,
    CreateAutomationRunHook $createAutomationRunHook
  ) {
    $this->contextFactory = $contextFactory;
    $this->segmentSubject = $segmentSubject;
    $this->subscriberSubject = $subscriberSubject;
    $this->orderSubject = $orderSubject;
    $this->orderStatusChangeSubject = $orderStatusChangeSubject;
    $this->customerSubject = $customerSubject;
    $this->someoneSubscribesTrigger = $someoneSubscribesTrigger;
    $this->userRegistrationTrigger = $userRegistrationTrigger;
    $this->orderStatusChangedTrigger = $orderStatusChangedTrigger;
    $this->sendEmailAction = $sendEmailAction;
    $this->automationEditorLoadingHooks = $automationEditorLoadingHooks;
    $this->createAutomationRunHook = $createAutomationRunHook;
  }

  public function register(Registry $registry): void {
    $registry->addContextFactory('mailpoet', function () {
      return $this->contextFactory->getContextData();
    });

    $registry->addSubject($this->segmentSubject);
    $registry->addSubject($this->subscriberSubject);
    $registry->addSubject($this->orderSubject);
    $registry->addSubject($this->orderStatusChangeSubject);
    $registry->addSubject($this->customerSubject);
    $registry->addTrigger($this->someoneSubscribesTrigger);
    $registry->addTrigger($this->userRegistrationTrigger);
    $registry->addTrigger($this->orderStatusChangedTrigger);
    $registry->addAction($this->sendEmailAction);

    // sync step args (subject, preheader, etc.) to email settings
    $registry->onBeforeAutomationStepSave(
      [$this->sendEmailAction, 'saveEmailSettings'],
      $this->sendEmailAction->getKey()
    );

    $this->automationEditorLoadingHooks->init();
    $this->createAutomationRunHook->init();
  }
}
