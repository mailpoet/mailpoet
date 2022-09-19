<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Workflows\Trigger;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\WP\Functions as WPFunctions;

class UserRegistrationTrigger implements Trigger {

  /** @var WorkflowStorage  */
  private $workflowStorage;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WorkflowStorage $workflowStorage,
    WPFunctions $wp
  ) {
    $this->workflowStorage = $workflowStorage;
    $this->wp = $wp;
  }

  public function getKey(): string {
    return 'mailpoet:wp-user-registered';
  }

  public function getName(): string {
    return __('Subscribed to segment', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'roles' => Builder::array(Builder::string())->required(),
    ]);
  }

  public function registerHooks(): void {
    $this->wp->addAction('mailpoet_user_registered', [$this, 'handleSubscription']);
  }

  public function handleSubscription(SubscriberEntity $subscriber): void {
    $segment = $this->getSegment($subscriber);
    $this->wp->doAction(Hooks::TRIGGER, $this, [
      [
        'key' => SegmentSubject::KEY,
        'args' => [
          'segment_id' => $segment->getId(),
        ],
      ],
      [
        'key' => SubscriberSubject::KEY,
        'args' => [
          'subscriber_id' => $subscriber->getId(),
        ],
      ],
    ]);
  }

  public function isTriggeredBy(WorkflowRun $workflowRun): bool {
    if ($workflowRun->getTriggerKey() !== $this->getKey()) {
      return false;
    }
    $workflow = $this->workflowStorage->getWorkflow($workflowRun->getWorkflowId(), $workflowRun->getVersionId());
    if (!$workflow) {
      return false;
    }

    $triggerData = $workflow->getTrigger($workflowRun->getTriggerKey());
    if (!$triggerData) {
      return false;
    }

    $segmentSubject = $workflowRun->requireSingleSubject(SegmentSubject::class);
    $segment = $segmentSubject->getSegment();
    if ($segment->getType() !== SegmentEntity::TYPE_WP_USERS) {
      return false;
    }
    $stepArgs = $triggerData->getArgs();
    if (!isset($stepArgs['roles']) || !is_array($stepArgs['roles'])) {
      return false;
    }

    $subscriber = $workflowRun->requireSingleSubject(SubscriberSubject::class);
    if (!$subscriber->getSubscriber()->isWPUser()) {
      return false;
    }
    $user = $this->wp->getUserBy('id', $subscriber->getSubscriber()->getWpUserId());
    if (!$user) {
      return false;
    }
    $roles = $stepArgs['roles'];
    $anyRole = '';
    if (in_array($anyRole, $roles, true)) {
      return true;
    }
    foreach ($user->roles as $userRole) {
      if (in_array($userRole, $roles, true)) {
        return true;
      }
    }
    return false;
  }

  private function getSegment(SubscriberEntity $subscriber): SegmentEntity {

    $segments = $subscriber->getSubscriberSegments()->toArray();
    if (!$segments) {
      throw new InvalidStateException();
    }
    $segment = null;
    foreach ($segments as $segment) {
      $segment = $segment->getSegment();
      if (!$segment || $segment->getType() !== SegmentEntity::TYPE_WP_USERS) {
        continue;
      }
      break;
    }
    if (!$segment || $segment->getType() !== SegmentEntity::TYPE_WP_USERS) {
      throw new InvalidStateException();
    }
    return $segment;
  }
}
