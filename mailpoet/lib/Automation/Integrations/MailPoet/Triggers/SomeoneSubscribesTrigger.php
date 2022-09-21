<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Workflows\Trigger;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\WP\Functions as WPFunctions;

class SomeoneSubscribesTrigger implements Trigger {

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
    return 'mailpoet:someone-subscribes';
  }

  public function getName(): string {
    return __('Someone subscribes', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'segment_ids' => Builder::array(Builder::number())->required(),
    ]);
  }

  public function registerHooks(): void {
    $this->wp->addAction('mailpoet_segment_subscribed', [$this, 'handleSubscription'], 10, 2);
  }

  public function handleSubscription(SubscriberSegmentEntity $subscriberSegment): void {
    $segment = $subscriberSegment->getSegment();
    $subscriber = $subscriberSegment->getSubscriber();

    if (!$segment || !$subscriber) {
      throw new InvalidStateException();
    }

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
    $stepArgs = $triggerData->getArgs();

    // Return true, when no segment list is defined (=any list) or the segment matches the definition.
    return (
      !isset($stepArgs['segment_ids'])
      || !is_array($stepArgs['segment_ids'])
      || !count($stepArgs['segment_ids'])
      || in_array($segment->getId(), $stepArgs['segment_ids'], true)
    );
  }
}
