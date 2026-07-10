<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use DateTimeImmutable;
use DateTimeInterface;
use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SubscribersRepositoryTest extends \MailPoetTest {
  /** @var SubscribersRepository */
  private $repository;
  /** @var SegmentsRepository */
  private $segmentRepository;
  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;
  /** @var SubscriberCustomFieldRepository */
  private $subscriberCustomFieldRepository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(SubscribersRepository::class);
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);
    $this->subscriberCustomFieldRepository = $this->diContainer->get(SubscriberCustomFieldRepository::class);
  }

  public function testItBulkTrashSubscribers(): void {
    $subscriberOne = $this->createSubscriber('one@trash.com');
    $subscriberTwo = $this->createSubscriber('two@trash.com');
    $subscriberThree = $this->createSubscriber('three@trash.com');

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();
    $subscriberThreeId = $subscriberThree->getId();

    $this->repository->bulkTrash([
      $subscriberOneId,
      $subscriberTwoId,
    ]);

    $this->entityManager->clear();

    // trashed subscriber
    $subscriberOne = $this->repository->findOneById($subscriberOneId);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberOne);
    verify($subscriberOne->getDeletedAt())->notNull();
    $subscriberTwo = $this->repository->findOneById($subscriberOneId);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberTwo);
    verify($subscriberTwo->getDeletedAt())->notNull();
    // don't trashed subscriber
    $subscriberThree = $this->repository->findOneById($subscriberThreeId);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberThree);
    verify($subscriberThree->getDeletedAt())->null();
  }

  public function testItBulkRestoreTrashedSubscribers(): void {
    $subscriberOne = $this->createSubscriber('one@restore.com', new DateTimeImmutable());
    $subscriberTwo = $this->createSubscriber('two@restore.com', new DateTimeImmutable());

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();

    $this->repository->bulkRestore([
      $subscriberOneId,
    ]);

    $this->entityManager->clear();

    // restored subscriber
    $subscriberOne = $this->repository->findOneById($subscriberOneId);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberOne);
    verify($subscriberOne->getDeletedAt())->null();
    // don't restored subscriber
    $subscriberTwo = $this->repository->findOneById($subscriberTwoId);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberTwo);
    verify($subscriberTwo->getDeletedAt())->notNull();
  }

  public function testItBulkUpdatesLastSendingAt(): void {
    $subscriberOne = $this->createSubscriber('one@e.com');
    $subscriberTwo = $this->createSubscriber('two@e.com');
    $subscriberThree = $this->createSubscriber('three@e.com');

    verify($subscriberOne->getLastSendingAt())->null();
    verify($subscriberTwo->getLastSendingAt())->null();
    verify($subscriberThree->getLastSendingAt())->null();
    $idsToUpdate = [
      $subscriberOne->getId(),
      $subscriberThree->getId(),
    ];
    $now = new DateTimeImmutable();
    $this->repository->bulkUpdateLastSendingAt($idsToUpdate, $now);
    $this->repository->refresh($subscriberOne);
    $this->repository->refresh($subscriberTwo);
    $this->repository->refresh($subscriberThree);
    $this->assertInstanceOf(DateTimeInterface::class, $subscriberOne->getLastSendingAt());
    verify($subscriberOne->getLastSendingAt()->getTimestamp())->equals($now->getTimestamp());
    verify($subscriberTwo->getLastSendingAt())->null();
    $this->assertInstanceOf(DateTimeInterface::class, $subscriberThree->getLastSendingAt());
    verify($subscriberThree->getLastSendingAt()->getTimestamp())->equals($now->getTimestamp());
  }

  public function testItBulkUpdatesEngagementScoreUpdatedAt(): void {
    $subscriberOne = $this->createSubscriber('one@e.com');
    $subscriberTwo = $this->createSubscriber('two@e.com');
    $subscriberThree = $this->createSubscriber('three@e.com');

    verify($subscriberOne->getEngagementScoreUpdatedAt())->null();
    verify($subscriberTwo->getEngagementScoreUpdatedAt())->null();
    verify($subscriberThree->getEngagementScoreUpdatedAt())->null();
    $idsToUpdate = [
      $subscriberOne->getId(),
      $subscriberThree->getId(),
    ];
    $now = new DateTimeImmutable();
    $this->repository->bulkUpdateEngagementScoreUpdatedAt($idsToUpdate, $now);
    $this->repository->refresh($subscriberOne);
    $this->repository->refresh($subscriberTwo);
    $this->repository->refresh($subscriberThree);
    $this->assertInstanceOf(DateTimeInterface::class, $subscriberOne->getEngagementScoreUpdatedAt());
    verify($subscriberOne->getEngagementScoreUpdatedAt()->getTimestamp())->equals($now->getTimestamp());
    verify($subscriberTwo->getEngagementScoreUpdatedAt())->null();
    $this->assertInstanceOf(DateTimeInterface::class, $subscriberThree->getEngagementScoreUpdatedAt());
    verify($subscriberThree->getEngagementScoreUpdatedAt()->getTimestamp())->equals($now->getTimestamp());
  }

  public function testBulkUpdatesOfEngagementScoreCanNullifyData(): void {
    $subscriberOne = $this->createSubscriber('one@e.com');
    $subscriberTwo = $this->createSubscriber('two@e.com');
    $subscriberThree = $this->createSubscriber('three@e.com');

    $now = new DateTimeImmutable();
    $subscriberOne->setEngagementScoreUpdatedAt($now);
    $subscriberTwo->setEngagementScoreUpdatedAt($now);
    $subscriberThree->setEngagementScoreUpdatedAt($now);
    $this->entityManager->persist($subscriberOne);
    $this->entityManager->persist($subscriberTwo);
    $this->entityManager->persist($subscriberThree);
    $this->entityManager->flush();

    $this->repository->refresh($subscriberOne);
    $this->repository->refresh($subscriberTwo);
    $this->repository->refresh($subscriberThree);
    $this->assertInstanceOf(DateTimeInterface::class, $subscriberOne->getEngagementScoreUpdatedAt());
    $this->assertInstanceOf(DateTimeInterface::class, $subscriberTwo->getEngagementScoreUpdatedAt());
    $this->assertInstanceOf(DateTimeInterface::class, $subscriberThree->getEngagementScoreUpdatedAt());

    $idsToUpdate = [
      $subscriberOne->getId(),
      $subscriberThree->getId(),
    ];
    $this->repository->bulkUpdateEngagementScoreUpdatedAt($idsToUpdate, null);

    $this->repository->refresh($subscriberOne);
    $this->repository->refresh($subscriberTwo);
    $this->repository->refresh($subscriberThree);

    verify($subscriberOne->getEngagementScoreUpdatedAt())->null();
    verify($subscriberTwo->getEngagementScoreUpdatedAt())->notNull();
    verify($subscriberThree->getEngagementScoreUpdatedAt())->null();
  }

  public function testItBulkDeleteSubscribers(): void {
    $subscriberOne = $this->createSubscriber('one@delete.com', new DateTimeImmutable());
    $subscriberTwo = $this->createSubscriber('two@delete.com', new DateTimeImmutable());
    $segmentOne = $this->segmentRepository->createOrUpdate('One Delete');
    $this->createSubscriberSegment($segmentOne, $subscriberOne);
    $this->createSubscriberSegment($segmentOne, $subscriberTwo);
    $customField = $this->createCustomField('CF');
    $this->createSubscriberCustomField($subscriberOne, $customField);
    $this->createSubscriberCustomField($subscriberTwo, $customField);

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();

    $this->repository->bulkDelete([
      $subscriberOneId,
    ]);

    $this->entityManager->clear();

    // deleted subscriber
    verify($this->repository->findOneById($subscriberOneId))->null();
    verify($this->subscriberSegmentRepository->findOneBy(['subscriber' => $subscriberOneId]))->null();
    verify($this->subscriberCustomFieldRepository->findOneBy(['subscriber' => $subscriberOneId]))->null();
    // don't restored subscriber
    $subscriberTwo = $this->repository->findOneById($subscriberTwoId);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberTwo);
    verify($subscriberTwo->getDeletedAt())->notNull();
    verify($this->subscriberSegmentRepository->findOneBy(['subscriber' => $subscriberTwoId]))->notNull();
    verify($this->subscriberCustomFieldRepository->findOneBy(['subscriber' => $subscriberTwoId]))->notNull();
  }

  public function testItBulkRemoveSubscribersFromSegment(): void {
    $subscriberOne = $this->createSubscriber('one@remove.com', new DateTimeImmutable());
    $subscriberTwo = $this->createSubscriber('two@remove.com', new DateTimeImmutable());
    $segmentOne = $this->segmentRepository->createOrUpdate('One Remove');
    $segmentTwo = $this->segmentRepository->createOrUpdate('Two Remove');
    $this->createSubscriberSegment($segmentOne, $subscriberOne);
    $this->createSubscriberSegment($segmentOne, $subscriberTwo);
    $this->createSubscriberSegment($segmentTwo, $subscriberOne);
    $this->createSubscriberSegment($segmentTwo, $subscriberTwo);

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();
    $segmentOneId = $segmentOne->getId();
    $segmentTwoId = $segmentTwo->getId();

    $this->repository->bulkRemoveFromSegment($segmentOne, [$subscriberOneId]);
    $this->repository->bulkRemoveFromSegment($segmentTwo, [$subscriberTwoId]);

    $this->entityManager->clear();

    // subscriber with removed segment one
    verify($this->repository->findOneById($subscriberOneId))->notNull();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberOneId,
      'segment' => $segmentOneId,
    ]))->null();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberOneId,
      'segment' => $segmentTwoId,
    ]))->notNull();

    // subscriber with removed segment two
    verify($this->repository->findOneById($subscriberTwoId))->notNull();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberTwoId,
      'segment' => $segmentTwoId,
    ]))->null();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberTwoId,
      'segment' => $segmentOneId,
    ]))->notNull();
  }

  public function testItBulkUnsubscribes(): void {
    $subscriberOne = $this->createSubscriber('one@removeAll.com', new DateTimeImmutable());
    $subscriberTwo = $this->createSubscriber('two@removeAll.com', new DateTimeImmutable());

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();

    $this->repository->bulkUnsubscribe([$subscriberOneId]);

    $this->entityManager->clear();

    // subscriber with removed segments
    $unsubscribedSubscriber = $this->repository->findOneById($subscriberOneId);
    verify($unsubscribedSubscriber)->notNull();
    $this->assertInstanceOf(SubscriberEntity::class, $unsubscribedSubscriber);
    verify($unsubscribedSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);

    // subscriber still subscribed
    $subscribedSubscriber = $this->repository->findOneById($subscriberTwoId);
    verify($subscribedSubscriber)->notNull();
    $this->assertInstanceOf(SubscriberEntity::class, $subscribedSubscriber);
    verify($subscribedSubscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItBulkRemoveSubscriberFromAllSegments(): void {
    $subscriberOne = $this->createSubscriber('one@removeAll.com', new DateTimeImmutable());
    $subscriberTwo = $this->createSubscriber('two@removeAll.com', new DateTimeImmutable());
    $segmentOne = $this->segmentRepository->createOrUpdate('One Remove All');
    $segmentTwo = $this->segmentRepository->createOrUpdate('Two Remove All');
    $this->createSubscriberSegment($segmentOne, $subscriberOne);
    $this->createSubscriberSegment($segmentOne, $subscriberTwo);
    $this->createSubscriberSegment($segmentTwo, $subscriberOne);
    $this->createSubscriberSegment($segmentTwo, $subscriberTwo);

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();
    $segmentOneId = $segmentOne->getId();
    $segmentTwoId = $segmentTwo->getId();

    $this->repository->bulkRemoveFromAllSegments([$subscriberOneId]);

    $this->entityManager->clear();

    // subscriber with removed segments
    verify($this->repository->findOneById($subscriberOneId))->notNull();
    verify($this->subscriberSegmentRepository->findBy(['subscriber' => $subscriberOneId]))->arrayCount(0);

    // subscriber with segments
    verify($this->repository->findOneById($subscriberTwoId))->notNull();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberTwoId,
      'segment' => $segmentOneId,
    ]))->notNull();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberTwoId,
      'segment' => $segmentTwoId,
    ]))->notNull();
  }

  public function testItBulkAddSubscribersToSegment(): void {
    $subscriberOne = $this->createSubscriber('one@add.com', new DateTimeImmutable());
    $subscriberTwo = $this->createSubscriber('two@add.com', new DateTimeImmutable());
    $segmentOne = $this->segmentRepository->createOrUpdate('One Add');

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();
    $segmentOneId = $segmentOne->getId();

    $this->repository->bulkAddToSegment($segmentOne, [$subscriberOneId], false);

    $this->entityManager->clear();

    // subscriber with segment
    verify($this->repository->findOneById($subscriberOneId))->notNull();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberOneId,
      'segment' => $segmentOneId,
    ]))->notNull();

    // subscriber without segment
    verify($this->repository->findOneById($subscriberTwoId))->notNull();
    verify($this->subscriberSegmentRepository->findBy(['subscriber' => $subscriberTwoId]))->arrayCount(0);
  }

  public function testItBulkAddToSegmentFiresSubscribedHookOnlyForNewGloballySubscribedSubscribers(): void {
    $segment = $this->segmentRepository->createOrUpdate('Bulk Add Hook');
    $newSubscriber = $this->createSubscriber('new@bulk-add-hook.com');
    $existingSubscriber = $this->createSubscriber('existing@bulk-add-hook.com');
    $unsubscribedSubscriber = $this->createSubscriber('unsubscribed@bulk-add-hook.com');
    $unsubscribedSubscriber->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->createSubscriberSegment($segment, $existingSubscriber);
    $this->entityManager->flush();

    $wp = new WPFunctions();
    $wp->removeAllActions('mailpoet_segment_subscribed');
    $firedFor = [];
    $wp->addAction('mailpoet_segment_subscribed', function (SubscriberSegmentEntity $subscriberSegment) use (&$firedFor) {
      $subscriber = $subscriberSegment->getSubscriber();
      $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
      $firedFor[] = $subscriber->getEmail();
    });

    $count = $this->repository->bulkAddToSegment($segment, [
      $newSubscriber->getId(),
      $existingSubscriber->getId(),
      $unsubscribedSubscriber->getId(),
    ], false);

    verify($count)->equals(2);
    verify($firedFor)->equals(['new@bulk-add-hook.com']);
  }

  public function testItBulkAddToSegmentCanSkipSubscribedHook(): void {
    $segment = $this->segmentRepository->createOrUpdate('Bulk Add Skip Hook');
    $subscriber = $this->createSubscriber('skip@bulk-add-hook.com');

    $wp = new WPFunctions();
    $wp->removeAllActions('mailpoet_segment_subscribed');
    $hookCalls = 0;
    $wp->addAction('mailpoet_segment_subscribed', function () use (&$hookCalls): void {
      $hookCalls++;
    });

    $count = $this->repository->bulkAddToSegment($segment, [$subscriber->getId()], true);

    verify($count)->equals(1);
    verify($hookCalls)->equals(0);
    $this->entityManager->clear();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriber->getId(),
      'segment' => $segment->getId(),
    ]))->notNull();
    $wp->removeAllActions('mailpoet_segment_subscribed');
  }

  public function testItBulMoveSubscribersToSegment(): void {
    $subscriberOne = $this->createSubscriber('one@move.com', new DateTimeImmutable());
    $subscriberTwo = $this->createSubscriber('two@move.com', new DateTimeImmutable());
    $segmentOne = $this->segmentRepository->createOrUpdate('One Move');
    $segmentTwo = $this->segmentRepository->createOrUpdate('Two Move');
    $this->createSubscriberSegment($segmentOne, $subscriberOne);
    $this->createSubscriberSegment($segmentTwo, $subscriberTwo);

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();
    $segmentOneId = $segmentOne->getId();
    $segmentTwoId = $segmentTwo->getId();

    $this->repository->bulkMoveToSegment($segmentTwo, [$subscriberOneId], false);

    $this->entityManager->clear();

    // subscriber moved to segment two
    verify($this->repository->findOneById($subscriberOneId))->notNull();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberOneId,
      'segment' => $segmentOneId,
    ]))->null();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberOneId,
      'segment' => $segmentTwoId,
    ]))->notNull();

    // subscriber which stay in segment two
    verify($this->repository->findOneById($subscriberTwoId))->notNull();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberTwoId,
      'segment' => $segmentOneId,
    ]))->null();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberTwoId,
      'segment' => $segmentTwoId,
    ]))->notNull();
  }

  public function testItBulkMoveToSegmentFiresSubscribedHookOnlyForNewDestinationMemberships(): void {
    $sourceSegment = $this->segmentRepository->createOrUpdate('Bulk Move Hook Source');
    $targetSegment = $this->segmentRepository->createOrUpdate('Bulk Move Hook Target');
    $newTargetSubscriber = $this->createSubscriber('new@bulk-move-hook.com');
    $existingTargetSubscriber = $this->createSubscriber('existing@bulk-move-hook.com');
    $this->createSubscriberSegment($sourceSegment, $newTargetSubscriber);
    $this->createSubscriberSegment($sourceSegment, $existingTargetSubscriber);
    $this->createSubscriberSegment($targetSegment, $existingTargetSubscriber);

    $wp = new WPFunctions();
    $wp->removeAllActions('mailpoet_segment_subscribed');
    $firedFor = [];
    $wp->addAction('mailpoet_segment_subscribed', function (SubscriberSegmentEntity $subscriberSegment) use (&$firedFor) {
      $subscriber = $subscriberSegment->getSubscriber();
      $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
      $firedFor[] = $subscriber->getEmail();
    });

    $count = $this->repository->bulkMoveToSegment($targetSegment, [
      $newTargetSubscriber->getId(),
      $existingTargetSubscriber->getId(),
    ], false);

    verify($count)->equals(2);
    verify($firedFor)->equals(['new@bulk-move-hook.com']);
  }

  public function testItBulkMoveToSegmentCanSkipSubscribedHook(): void {
    $sourceSegment = $this->segmentRepository->createOrUpdate('Bulk Move Skip Hook Source');
    $targetSegment = $this->segmentRepository->createOrUpdate('Bulk Move Skip Hook Target');
    $subscriber = $this->createSubscriber('skip@bulk-move-hook.com');
    $this->createSubscriberSegment($sourceSegment, $subscriber);
    $subscriberId = (int)$subscriber->getId();
    $sourceSegmentId = (int)$sourceSegment->getId();
    $targetSegmentId = (int)$targetSegment->getId();

    $wp = new WPFunctions();
    $wp->removeAllActions('mailpoet_segment_subscribed');
    $hookCalls = 0;
    $wp->addAction('mailpoet_segment_subscribed', function () use (&$hookCalls): void {
      $hookCalls++;
    });

    $count = $this->repository->bulkMoveToSegment($targetSegment, [$subscriberId], true);

    verify($count)->equals(1);
    verify($hookCalls)->equals(0);
    $this->entityManager->clear();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberId,
      'segment' => $sourceSegmentId,
    ]))->null();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberId,
      'segment' => $targetSegmentId,
    ]))->notNull();
    $wp->removeAllActions('mailpoet_segment_subscribed');
  }

  public function testItBulkMoveToSegmentFiresSubscribedHookWhenDestinationMembershipWasUnsubscribed(): void {
    $sourceSegment = $this->segmentRepository->createOrUpdate('Bulk Move Reactivation Source');
    $targetSegment = $this->segmentRepository->createOrUpdate('Bulk Move Reactivation Target');
    $subscriber = $this->createSubscriber('reactivated@bulk-move-hook.com');
    $this->createSubscriberSegment($sourceSegment, $subscriber);
    $targetMembership = $this->createSubscriberSegment($targetSegment, $subscriber);
    $targetMembership->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->entityManager->flush();

    $wp = new WPFunctions();
    $wp->removeAllActions('mailpoet_segment_subscribed');
    $firedFor = [];
    $wp->addAction('mailpoet_segment_subscribed', function (SubscriberSegmentEntity $subscriberSegment) use (&$firedFor) {
      $subscriber = $subscriberSegment->getSubscriber();
      $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
      $firedFor[] = $subscriber->getEmail();
    });

    $count = $this->repository->bulkMoveToSegment($targetSegment, [$subscriber->getId()], false);

    verify($count)->equals(1);
    verify($firedFor)->equals(['reactivated@bulk-move-hook.com']);
    $this->entityManager->clear();
    $targetMembership = $this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriber->getId(),
      'segment' => $targetSegment->getId(),
    ]);
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $targetMembership);
    verify($targetMembership->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItRecalculatesCountsAndNotifiesChangesBeforeFailingSubscribedHook(): void {
    $sourceSegment = $this->segmentRepository->createOrUpdate('Bulk Move Hook Failure Source');
    $targetSegment = $this->segmentRepository->createOrUpdate('Bulk Move Hook Failure Target');
    $subscriber = $this->createSubscriber('failure@bulk-move-hook.com');
    $this->createSubscriberSegment($sourceSegment, $subscriber);
    $subscriberId = (int)$subscriber->getId();
    $events = [];
    $notifiedIds = [];
    $changesNotifier = $this->make(SubscriberChangesNotifier::class, [
      'subscribersUpdated' => function (array $subscriberIds) use (&$events, &$notifiedIds): void {
        $events[] = 'notified';
        $notifiedIds = $subscriberIds;
      },
    ]);
    $repository = $this->getServiceWithOverrides(SubscribersRepository::class, [
      'changesNotifier' => $changesNotifier,
    ]);

    $wp = new WPFunctions();
    $wp->removeAllActions('mailpoet_segment_subscribed');
    $wp->addAction('mailpoet_segment_subscribed', function () use (&$events): void {
      $events[] = 'hook';
      throw new \RuntimeException('Subscribed hook failed');
    });

    try {
      $repository->bulkMoveToSegment($targetSegment, [$subscriberId], false);
      $this->fail('Expected subscribed hook failure');
    } catch (\RuntimeException $exception) {
      verify($exception->getMessage())->equals('Subscribed hook failed');
    }

    $this->entityManager->clear();
    $subscriber = $repository->findOneById($subscriberId);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber->getSegmentsCount())->equals(1);
    verify($notifiedIds)->equals([$subscriberId]);
    verify($events)->equals(['notified', 'hook']);
  }

  public function testItDoesntRemovePermanentlyWordpressSubscriber(): void {
    $subscriber = $this->createSubscriber('wpsubscriber@delete.com');
    $segment = $this->segmentRepository->createOrUpdate('WP subscriber delete guard');
    $this->createSubscriberSegment($segment, $subscriber);
    $subscriber->setWpUserId(1);
    $this->repository->flush();
    $this->entityManager->clear();
    $subscriberId = $subscriber->getId();
    $segmentId = $segment->getId();

    $count = $this->repository->bulkDelete([$subscriber->getId()]);

    verify($count)->equals(0);
    verify($this->repository->findOneById($subscriberId))->notNull();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberId,
      'segment' => $segmentId,
    ]))->notNull();
  }

  public function testItDoesntRemovePermanentlyWoocommerceSubscriber(): void {
    $subscriber = $this->createSubscriber('wcsubscriber@delete.com');
    $segment = $this->segmentRepository->createOrUpdate('WC subscriber delete guard');
    $this->createSubscriberSegment($segment, $subscriber);
    $subscriber->setIsWoocommerceUser(true);
    $this->repository->flush();
    $this->entityManager->clear();
    $subscriberId = $subscriber->getId();
    $segmentId = $segment->getId();

    $count = $this->repository->bulkDelete([$subscriberId]);

    verify($count)->equals(0);
    verify($this->repository->findOneById($subscriberId))->notNull();
    verify($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberId,
      'segment' => $segmentId,
    ]))->notNull();
  }

  public function testItGetsMaxSubscriberId(): void {
    // check if equals to zero when no subscribers
    verify($this->repository->getMaxSubscriberId())->equals(0);
    // check if equals to max subscriber id
    $this->createSubscriber('sub1@test.com');
    $subscriberTwo = $this->createSubscriber('sub2@test.com');
    verify($this->repository->getMaxSubscriberId())->equals($subscriberTwo->getId());
  }

  public function testRemoveOrphanedSubscribersFromWpSegment(): void {
    $wpUserId1 = $this->tester->createWordPressUser('subscriber1@email.com', 'author');
    $wpUserId2 = $this->tester->createWordPressUser('subscriber2@email.com', 'author');
    $wpUserId3 = $this->tester->createWordPressUser('subscriber3@email.com', 'author');
    $subscriber1 = $this->repository->findOneBy(['wpUserId' => $wpUserId1]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber1Id = $subscriber1->getId();
    $subscriber2 = $this->repository->findOneBy(['wpUserId' => $wpUserId2]);
    $subscriber3 = $this->repository->findOneBy(['wpUserId' => $wpUserId3]);

    $this->tester->deleteWPUserFromDatabase($wpUserId1);

    $this->repository->removeOrphanedSubscribersFromWpSegment();
    $this->entityManager->clear();

    // Subscriber for the deleted WP user is preserved but unlinked and marked
    // with the WORDPRESS_USER_DELETED source. Subscribers for live WP users are untouched.
    $subscribers = $this->repository->findAll();
    $this->assertCount(3, $subscribers);

    $detachedSubscriber = $this->repository->findOneById($subscriber1Id);
    $this->assertInstanceOf(SubscriberEntity::class, $detachedSubscriber);
    $this->assertNull($detachedSubscriber->getWpUserId());
    $this->assertSame(Source::WORDPRESS_USER_DELETED, $detachedSubscriber->getSource());

    $stillLinked2 = $this->repository->findOneBy(['wpUserId' => $wpUserId2]);
    $this->assertInstanceOf(SubscriberEntity::class, $stillLinked2);
    $stillLinked3 = $this->repository->findOneBy(['wpUserId' => $wpUserId3]);
    $this->assertInstanceOf(SubscriberEntity::class, $stillLinked3);
    $this->assertSame($subscriber2 ? $subscriber2->getId() : null, $stillLinked2->getId());
    $this->assertSame($subscriber3 ? $subscriber3->getId() : null, $stillLinked3->getId());
  }

  public function testRemoveByWpUserIds(): void {
    $wpUserId1 = $this->tester->createWordPressUser('subscriber1@email.com', 'author');
    $wpUserId2 = $this->tester->createWordPressUser('subscriber2@email.com', 'author');
    $wpUserId3 = $this->tester->createWordPressUser('subscriber3@email.com', 'author');

    $subscriber1 = $this->repository->findOneBy(['wpUserId' => $wpUserId1]);
    $subscriber2 = $this->repository->findOneBy(['wpUserId' => $wpUserId2]);
    $subscriber3 = $this->repository->findOneBy(['wpUserId' => $wpUserId3]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);

    $subscriber1Id = (int)$subscriber1->getId();
    $subscriber2Id = (int)$subscriber2->getId();
    $subscriber3Id = (int)$subscriber3->getId();

    // Attach related rows to each subscriber so we can assert they get cleaned up.
    $segment = $this->segmentRepository->createOrUpdate('Removed WP users segment');
    $customField = $this->createCustomField('WP users CF');
    $tag = $this->createTag('WP users tag');
    foreach ([$subscriber1, $subscriber2, $subscriber3] as $subscriber) {
      $this->createSubscriberSegment($segment, $subscriber);
      $this->createSubscriberCustomField($subscriber, $customField);
      $this->createSubscriberTag($subscriber, $tag);
    }

    $subscriberTagRepository = $this->entityManager->getRepository(SubscriberTagEntity::class);

    $deletedRows = $this->repository->removeByWpUserIds([$wpUserId1, $wpUserId2]);
    $this->entityManager->clear();

    $this->assertSame(2, $deletedRows);
    $subscribers = $this->repository->findAll();
    $this->assertCount(1, $subscribers);
    $this->assertSame($subscriber3Id, $subscribers[0]->getId());

    // Related rows of the removed subscribers are gone, including the WP-Users
    // segment memberships created during synchronization.
    foreach ([$subscriber1Id, $subscriber2Id] as $removedId) {
      verify($this->subscriberSegmentRepository->findBy(['subscriber' => $removedId]))->empty();
      verify($this->subscriberCustomFieldRepository->findOneBy(['subscriber' => $removedId]))->null();
      verify($subscriberTagRepository->findOneBy(['subscriber' => $removedId]))->null();
    }

    // Related rows of the surviving subscriber are untouched.
    verify($this->subscriberSegmentRepository->findOneBy(['subscriber' => $subscriber3Id]))->notNull();
    verify($this->subscriberCustomFieldRepository->findOneBy(['subscriber' => $subscriber3Id]))->notNull();
    verify($subscriberTagRepository->findOneBy(['subscriber' => $subscriber3Id]))->notNull();
  }

  public function testItDeletesOnlyEligibleUnconfirmedSubscribersForCleanup(): void {
    $now = Carbon::now()->millisecond(0);
    $old = $now->copy()->subDays(31);
    $recent = $now->copy()->subDays(10);
    $cutoff = $now->copy()->subDays(30);

    $legacyEligible = (new SubscriberFactory())
      ->withEmail('legacy-eligible@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCreatedAt($old)
      ->create();
    $resentEligible = (new SubscriberFactory())
      ->withEmail('resent-eligible@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCreatedAt($recent)
      ->withLastConfirmationEmailSentAt($old)
      ->create();
    $recentResent = (new SubscriberFactory())
      ->withEmail('recent-resent@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCreatedAt($old)
      ->withLastConfirmationEmailSentAt($recent)
      ->create();
    $recentCreated = (new SubscriberFactory())
      ->withEmail('recent-created@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCreatedAt($recent)
      ->create();
    $recentSubscribed = (new SubscriberFactory())
      ->withEmail('recent-subscribed@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCreatedAt($old)
      ->withLastSubscribedAt($recent)
      ->create();
    $subscribed = (new SubscriberFactory())
      ->withEmail('subscribed@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withCreatedAt($old)
      ->create();
    $trashed = (new SubscriberFactory())
      ->withEmail('trashed@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCreatedAt($old)
      ->withDeletedAt($old)
      ->create();
    $wpLinked = (new SubscriberFactory())
      ->withEmail('wp-linked@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCreatedAt($old)
      ->withWpUserId(123)
      ->create();
    $wooLinked = (new SubscriberFactory())
      ->withEmail('woo-linked@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCreatedAt($old)
      ->withIsWooCommerceUser(true)
      ->create();
    $segment = $this->segmentRepository->createOrUpdate('Cleanup retained relationships');
    $customField = $this->createCustomField('Cleanup retained field');
    $tag = $this->createTag('Cleanup retained tag');
    foreach ([$trashed, $wpLinked, $wooLinked] as $excludedSubscriber) {
      $this->createSubscriberSegment($segment, $excludedSubscriber);
      $this->createSubscriberCustomField($excludedSubscriber, $customField);
      $this->createSubscriberTag($excludedSubscriber, $tag);
    }
    $withoutDates = (new SubscriberFactory())
      ->withEmail('without-dates@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();
    $this->entityManager->getConnection()->executeStatement(
      'UPDATE ' . $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName() . ' SET created_at = NULL WHERE id = :id',
      ['id' => $withoutDates->getId()]
    );
    $this->entityManager->clear();

    $deletedIds = $this->repository->deleteUnconfirmedSubscribersForCleanup($cutoff, 10);

    verify($deletedIds)->equals([$legacyEligible->getId(), $resentEligible->getId()]);
    verify($this->repository->findOneById($legacyEligible->getId()))->null();
    verify($this->repository->findOneById($resentEligible->getId()))->null();
    foreach ([$recentResent, $recentCreated, $recentSubscribed, $subscribed, $trashed, $wpLinked, $wooLinked, $withoutDates] as $subscriber) {
      verify($this->repository->findOneById($subscriber->getId()))->notNull();
    }
    foreach ([$trashed, $wpLinked, $wooLinked] as $excludedSubscriber) {
      verify($this->subscriberSegmentRepository->findOneBy(['subscriber' => $excludedSubscriber->getId()]))->notNull();
      verify($this->subscriberCustomFieldRepository->findOneBy(['subscriber' => $excludedSubscriber->getId()]))->notNull();
      verify($this->entityManager->getRepository(SubscriberTagEntity::class)->findOneBy([
        'subscriber' => $excludedSubscriber->getId(),
      ]))->notNull();
    }
  }

  public function testUnconfirmedSubscribersCleanupRespectsLimitAndIdOrdering(): void {
    $old = Carbon::now()->millisecond(0)->subDays(31);
    $first = (new SubscriberFactory())->withEmail('cleanup-first@example.com')->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->withCreatedAt($old)->create();
    $second = (new SubscriberFactory())->withEmail('cleanup-second@example.com')->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->withCreatedAt($old)->create();
    $third = (new SubscriberFactory())->withEmail('cleanup-third@example.com')->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->withCreatedAt($old)->create();

    $deletedIds = $this->repository->deleteUnconfirmedSubscribersForCleanup(Carbon::now()->subDays(30), 2);

    verify($deletedIds)->equals([$first->getId(), $second->getId()]);
    verify($this->repository->findOneById($third->getId()))->notNull();
  }

  public function testPublicConfirmationEmailClaimIsReleasedWhenSendFails(): void {
    $subscriber = (new SubscriberFactory())
      ->withEmail('claim-released@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCountConfirmations(2)
      ->create();

    $result = $this->repository->sendPublicConfirmationEmailWithCap($subscriber, 3, function(): bool {
      return false;
    });

    verify($result)->false();
    $this->repository->refresh($subscriber);
    verify($subscriber->getConfirmationsCount())->equals(2);
    verify($subscriber->getLastConfirmationEmailSentAt())->null();
  }

  public function testPublicConfirmationEmailClaimPreventsSendAtCap(): void {
    $subscriber = (new SubscriberFactory())
      ->withEmail('claim-capped@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCountConfirmations(3)
      ->create();
    $sent = false;

    $result = $this->repository->sendPublicConfirmationEmailWithCap($subscriber, 3, function() use (&$sent): bool {
      $sent = true;
      return true;
    });

    verify($result)->false();
    verify($sent)->false();
    $this->repository->refresh($subscriber);
    verify($subscriber->getConfirmationsCount())->equals(3);
  }
  
  public function testEngagementUpdatesReactivateInactiveSubscribers(): void {
    $engagementUpdates = [
      fn(SubscriberEntity $s) => $this->repository->maybeUpdateLastEngagement($s),
      fn(SubscriberEntity $s) => $this->repository->maybeUpdateLastOpenAt($s),
      fn(SubscriberEntity $s) => $this->repository->maybeUpdateLastClickAt($s),
      fn(SubscriberEntity $s) => $this->repository->maybeUpdateLastPurchaseAt($s),
      fn(SubscriberEntity $s) => $this->repository->maybeUpdateLastPageViewAt($s),
    ];

    foreach ($engagementUpdates as $index => $update) {
      $subscriber = $this->createSubscriber("inactive-{$index}@reactivate.com");
      $subscriber->setStatus(SubscriberEntity::STATUS_INACTIVE);
      $this->entityManager->flush();

      $update($subscriber);

      verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
      verify($subscriber->getLastEngagementAt())->notNull();
    }
  }

  public function testEngagementUpdatesDoNotChangeStatusOfActiveSubscriber(): void {
    $subscriber = $this->createSubscriber('active@reactivate.com');
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);

    $this->repository->maybeUpdateLastOpenAt($subscriber);

    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    verify($subscriber->getLastEngagementAt())->notNull();
  }

  public function testItBulkAddTagFiresTagAddedHookOnlyForNewlyTaggedSubscribers(): void {
    $tag = $this->createTag('Bulk added tag');
    $subscriberOne = $this->createSubscriber('one@bulk-add-tag.com');
    $subscriberTwo = $this->createSubscriber('two@bulk-add-tag.com');
    $this->createSubscriberTag($subscriberTwo, $tag);

    $wp = new WPFunctions();
    $wp->removeAllActions('mailpoet_subscriber_tag_added');
    $firedFor = [];
    $wp->addAction('mailpoet_subscriber_tag_added', function (SubscriberTagEntity $subscriberTag) use (&$firedFor) {
      $subscriber = $subscriberTag->getSubscriber();
      $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
      $firedFor[] = $subscriber->getEmail();
    });

    $count = $this->repository->bulkAddTag($tag, [$subscriberOne->getId(), $subscriberTwo->getId()], false);

    verify($count)->equals(1);
    verify($firedFor)->equals(['one@bulk-add-tag.com']);
    $subscriberTags = $this->entityManager->getRepository(SubscriberTagEntity::class)->findBy(['tag' => $tag]);
    verify($subscriberTags)->arrayCount(2);
    $wp->removeAllActions('mailpoet_subscriber_tag_added');
  }

  public function testItBulkAddTagCanSkipTagAddedHook(): void {
    $tag = $this->createTag('Bulk added tag without hook');
    $subscriber = $this->createSubscriber('skip@bulk-add-tag.com');

    $wp = new WPFunctions();
    $wp->removeAllActions('mailpoet_subscriber_tag_added');
    $hookCalls = 0;
    $wp->addAction('mailpoet_subscriber_tag_added', function () use (&$hookCalls): void {
      $hookCalls++;
    });

    $count = $this->repository->bulkAddTag($tag, [$subscriber->getId()], true);

    verify($count)->equals(1);
    verify($hookCalls)->equals(0);
    $subscriberTags = $this->entityManager->getRepository(SubscriberTagEntity::class)->findBy([
      'subscriber' => $subscriber,
      'tag' => $tag,
    ]);
    verify($subscriberTags)->arrayCount(1);
    $wp->removeAllActions('mailpoet_subscriber_tag_added');
  }

  public function testItBulkRemoveTagFiresTagRemovedHookOnlyForSubscribersThatHadTag(): void {
    $tag = $this->createTag('Bulk removed tag');
    $subscriberOne = $this->createSubscriber('one@bulk-remove-tag.com');
    $subscriberTwo = $this->createSubscriber('two@bulk-remove-tag.com');
    $this->createSubscriberTag($subscriberOne, $tag);

    $wp = new WPFunctions();
    $wp->removeAllActions('mailpoet_subscriber_tag_removed');
    $firedFor = [];
    $wp->addAction('mailpoet_subscriber_tag_removed', function (SubscriberTagEntity $subscriberTag) use (&$firedFor) {
      $subscriber = $subscriberTag->getSubscriber();
      $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
      $firedFor[] = $subscriber->getEmail();
    });

    $count = $this->repository->bulkRemoveTag($tag, [$subscriberOne->getId(), $subscriberTwo->getId()], false);

    verify($count)->equals(1);
    verify($firedFor)->equals(['one@bulk-remove-tag.com']);
    $this->entityManager->clear();
    $subscriberTags = $this->entityManager->getRepository(SubscriberTagEntity::class)->findBy(['tag' => $tag]);
    verify($subscriberTags)->arrayCount(0);
    $wp->removeAllActions('mailpoet_subscriber_tag_removed');
  }

  public function testItBulkRemoveTagCanSkipTagRemovedHook(): void {
    $tag = $this->createTag('Bulk removed tag without hook');
    $subscriber = $this->createSubscriber('skip@bulk-remove-tag.com');
    $this->createSubscriberTag($subscriber, $tag);

    $wp = new WPFunctions();
    $wp->removeAllActions('mailpoet_subscriber_tag_removed');
    $hookCalls = 0;
    $wp->addAction('mailpoet_subscriber_tag_removed', function () use (&$hookCalls): void {
      $hookCalls++;
    });

    $count = $this->repository->bulkRemoveTag($tag, [$subscriber->getId()], true);

    verify($count)->equals(1);
    verify($hookCalls)->equals(0);
    $this->entityManager->clear();
    $subscriberTags = $this->entityManager->getRepository(SubscriberTagEntity::class)->findBy([
      'subscriber' => $subscriber->getId(),
      'tag' => $tag->getId(),
    ]);
    verify($subscriberTags)->arrayCount(0);
    $wp->removeAllActions('mailpoet_subscriber_tag_removed');
  }

  private function createSubscriber(string $email, ?DateTimeImmutable $deletedAt = null): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setFirstName('John');
    $subscriber->setLastName('Doe');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setDeletedAt($deletedAt);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createSubscriberSegment(SegmentEntity $segment, SubscriberEntity $subscriber): SubscriberSegmentEntity {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();
    return $subscriberSegment;
  }

  private function createCustomField(string $name): CustomFieldEntity {
    $customField = new CustomFieldEntity();
    $customField->setName($name);
    $customField->setType(CustomFieldEntity::TYPE_TEXT);
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    return $customField;
  }

  private function createTag(string $name): TagEntity {
    $tag = new TagEntity($name);
    $this->entityManager->persist($tag);
    $this->entityManager->flush();
    return $tag;
  }

  private function createSubscriberCustomField(SubscriberEntity $subscriber, CustomFieldEntity $customField): SubscriberCustomFieldEntity {
    $subscirberCustomField = new SubscriberCustomFieldEntity($subscriber, $customField, 'some value');
    $this->entityManager->persist($subscirberCustomField);
    $this->entityManager->flush();
    return $subscirberCustomField;
  }

  private function createSubscriberTag(SubscriberEntity $subscriber, TagEntity $tag): SubscriberTagEntity {
    $subscriberTag = new SubscriberTagEntity($tag, $subscriber);
    $this->entityManager->persist($subscriberTag);
    $this->entityManager->flush();
    return $subscriberTag;
  }
}
