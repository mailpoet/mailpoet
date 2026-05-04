<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\WP\Functions as WPFunctions;

class SomeoneUnsubscribesTrigger implements Trigger {
  const KEY = 'mailpoet:someone-unsubscribes';
  const RULE_ID = 'unsubscribes-trigger-no-send-email';

  /** @var WPFunctions */
  private $wp;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    WPFunctions $wp,
    SubscribersRepository $subscribersRepository
  ) {
    $this->wp = $wp;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function getKey(): string {
    return self::KEY;
  }

  public function getName(): string {
    // translators: automation trigger title
    return __('Email subscriber unsubscribed', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object();
  }

  public function getSubjectKeys(): array {
    return [
      SubscriberSubject::KEY,
    ];
  }

  public function validate(StepValidationArgs $args): void {
  }

  public function validateAutomation(Automation $automation): void {
    if (!$automation->needsFullValidation()) {
      return;
    }

    $hasUnsubscribeTrigger = false;
    $hasSendEmailAction = false;
    foreach ($automation->getSteps() as $step) {
      if ($step->getKey() === self::KEY) {
        $hasUnsubscribeTrigger = true;
      }
      if ($step->getKey() === SendEmailAction::KEY) {
        $hasSendEmailAction = true;
      }
    }

    if ($hasUnsubscribeTrigger && $hasSendEmailAction) {
      throw Exceptions::automationStructureNotValid(
        __('The "Email subscriber unsubscribed" trigger cannot be used together with a "Send email" action. Remove the "Send email" action to activate this automation.', 'mailpoet'),
        self::RULE_ID
      );
    }
  }

  public function registerHooks(): void {
    $this->wp->addAction(SubscriberEntity::HOOK_SUBSCRIBER_STATUS_CHANGED, [$this, 'handleStatusChange']);
  }

  public function handleStatusChange(int $subscriberId): void {
    $subscriber = $this->subscribersRepository->findOneById($subscriberId);
    if (!$subscriber || $subscriber->getStatus() !== SubscriberEntity::STATUS_UNSUBSCRIBED) {
      return;
    }

    $this->wp->doAction(Hooks::TRIGGER, $this, [
      new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriber->getId()]),
    ]);
  }

  public function isTriggeredBy(StepRunArgs $args): bool {
    return true;
  }
}
