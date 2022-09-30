<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Workflows\Trigger;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SegmentPayload;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\Common\Collections\Criteria;

class UserRegistrationTrigger implements Trigger {

  /** @var SegmentsRepository  */
  private $segmentsRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SegmentsRepository $segmentsRepository,
    WPFunctions $wp
  ) {
    $this->segmentsRepository = $segmentsRepository;
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

  public function getSubjectKeys(): array {
    return [
      SegmentSubject::KEY,
      SubscriberSubject::KEY,
    ];
  }

  public function registerHooks(): void {
    $this->wp->addAction('mailpoet_user_registered', [$this, 'handleSubscription']);
  }

  public function handleSubscription(SubscriberEntity $subscriber): void {
    $segment = $this->getWpSegment($subscriber);
    $this->wp->doAction(Hooks::TRIGGER, $this, [
      new Subject(SegmentSubject::KEY, ['segment_id' => $segment->getId()]),
      new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriber->getId()]),
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

  private function getWpSegment(SubscriberEntity $subscriber): SegmentEntity {
    $wpUserSegment = $this->segmentsRepository->getWPUsersSegment();

    $criteria = Criteria::create()->where(Criteria::expr()->eq('segment', $wpUserSegment));
    $segment = $subscriber->getSubscriberSegments()->matching($criteria)->first() ?: null;
    if (!$segment || !$segment->getSegment()) {
      throw new InvalidStateException();
    }
    return $segment->getSegment();
  }
}
