<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Workflows\Trigger;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\WP\Functions as WPFunctions;

class SegmentSubscribedTrigger implements Trigger {
  /** @var SegmentSubject */
  private $segmentSubject;

  /** @var SubscriberSubject */
  private $subscriberSubject;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SegmentSubject $segmentSubject,
    SubscriberSubject $subscriberSubject,
    WPFunctions $wp
  ) {
    $this->segmentSubject = $segmentSubject;
    $this->subscriberSubject = $subscriberSubject;
    $this->wp = $wp;
  }

  public function getKey(): string {
    return 'mailpoet:segment:subscribed';
  }

  public function getName(): string {
    return __('Subscribed to segment');
  }

  public function getSubjects(): array {
    return [
      $this->segmentSubject,
      $this->subscriberSubject,
    ];
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

    $this->segmentSubject->load(['segment_id' => $segment->getId()]);
    $this->subscriberSubject->load(['subscriber_id' => $subscriber->getId()]);

    $this->wp->doAction(Hooks::TRIGGER, $this, [
      $this->segmentSubject,
      $this->subscriberSubject,
    ]);
  }
}
