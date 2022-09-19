<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Workflows\Subject;
use MailPoet\Automation\Engine\Workflows\Trigger;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\WP\Functions as WPFunctions;

class SomeoneSubscribesTrigger implements Trigger {

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
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

  public function getSubjectKeys(): array {
    return [
      SubscriberSubject::KEY,
      SegmentSubject::KEY,
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

    $this->wp->doAction(Hooks::TRIGGER, $this, [
      new Subject(SegmentSubject::KEY, ['segment_id' => $segment->getId()]),
      new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriber->getId()]),
    ]);
  }

  public function isTriggeredBy(array $args, Subject ...$subjects): bool {

    $segment = null;
    foreach ($subjects as $subject) {
      if (!$subject instanceof SegmentSubject) {
        continue;
      }
      /**
       * @var SegmentSubject $subject
       */
      $segment = $subject->getSegment();
    }

    // Return true, when no segment list is defined (=any list) or the segment matches the definition.
    return (
      !$segment
      || !isset($args['segment_ids'])
      || !is_array($args['segment_ids'])
      || !count($args['segment_ids'])
      || in_array($segment->getId(), $args['segment_ids'], true)
    );
  }
}
