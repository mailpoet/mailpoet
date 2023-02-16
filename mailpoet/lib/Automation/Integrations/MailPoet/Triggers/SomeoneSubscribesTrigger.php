<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SegmentPayload;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\Segments\DynamicSegments\FilterHandler;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SomeoneSubscribesTrigger implements Trigger {
  const KEY = 'mailpoet:someone-subscribes';

  /** @var WPFunctions */
  private $wp;

  /** @var EntityManager */
  private $entityManager;

  /** @var SegmentsRepository  */
  private $segmentsRepository;

  /** @var FilterHandler */
  private $filterHandler;

  public function __construct(
    WPFunctions $wp,
    EntityManager $entityManager,
    SegmentsRepository $segmentsRepository,
    FilterHandler $filterHandler
  ) {
    $this->wp = $wp;
    $this->entityManager = $entityManager;
    $this->segmentsRepository = $segmentsRepository;
    $this->filterHandler = $filterHandler;
  }

  public function getKey(): string {
    return 'mailpoet:someone-subscribes';
  }

  public function getName(): string {
    return __('Someone subscribes', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'segment_ids' => Builder::array(Builder::number()),
    ]);
  }

  public function getSubjectKeys(): array {
    return [
      SubscriberSubject::KEY,
      SegmentSubject::KEY,
    ];
  }

  public function validate(StepValidationArgs $args): void {
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

  public function isTriggeredBy(StepRunArgs $args): bool {
    $segmentId = $args->getSinglePayloadByClass(SegmentPayload::class)->getId();
    $segment = $this->segmentsRepository->findOneById($segmentId);
    if (!$segment || $segment->getType() !== SegmentEntity::TYPE_DEFAULT) {
      return false;
    }

    // Triggers when no segment IDs defined (= any segment) or the current segment payload exists in defined segment IDs.
    $triggerArgs = $args->getStep()->getArgs();
    $segmentIds = $triggerArgs['segment_ids'] ?? [];
    if (is_array($segmentIds) && $segmentIds && !in_array($segmentId, $segmentIds, true)) {
      return false;
    }

    // Dynamic segments.
    $dynamicSegmentIds = [6];
    foreach ($dynamicSegmentIds as $id) {
      $dynamicSegment = $this->segmentsRepository->findOneById($id);
      if (!$dynamicSegment || !$this->matchesDynamicSegments($args, $dynamicSegment)) {
        return false;
      }
    }

    return true;
  }

  private function matchesDynamicSegments(StepRunArgs $args, SegmentEntity $segment): bool {
    $subscriberId = $args->getSinglePayloadByClass(SubscriberPayload::class)->getId();
    var_dump($subscriberId);
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $qb = $this->entityManager->getConnection()->createQueryBuilder()
      ->select('id')
      ->from($subscribersTable)
      ->where('id = :subscriberId')
      ->setParameter('subscriberId', $subscriberId);
    return (bool)$this->filterHandler->apply($qb, $segment)->execute()->fetchOne();
  }
}
