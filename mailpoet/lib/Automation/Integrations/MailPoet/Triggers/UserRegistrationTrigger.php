<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Workflows\Trigger;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SegmentPayload;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\WP\Functions as WPFunctions;

class UserRegistrationTrigger implements Trigger {


  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
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

  public function isTriggeredBy(StepRunArgs $args): bool {
    $segmentPayload = $args->getSinglePayloadByClass(SegmentPayload::class);
    if ($segmentPayload->getType() !== SegmentEntity::TYPE_WP_USERS) {
      return false;
    }

    $subscriberPayload = $args->getSinglePayloadByClass(SubscriberPayload::class);
    if (!$subscriberPayload->isWPUser()) {
      return false;
    }

    $user = $this->wp->getUserBy('id', $subscriberPayload->getWpUserId());
    if (!$user) {
      return false;
    }

    $triggerArgs = $args->getStep()->getArgs();
    $roles = $triggerArgs['roles'] ?? [];
    return !is_array($roles) || !$roles || count(array_intersect($user->roles, $roles)) > 0;
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
