<?php

namespace MailPoet\Segments;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Subscribers\Source;

class SegmentSubscribersRepositoryTest extends \MailPoetTest {

  /** @var SegmentSubscribersRepository */
  private $repository;

  /** @var SegmentsRepository */
  private $segmentRepository;

  public function _before() {
    parent::_before();
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->repository = $this->diContainer->get(SegmentSubscribersRepository::class);
    $this->cleanup();
  }

  public function testItReturnsOnlySubscribedSubscribersForStaticSegment() {
    $segment = $this->segmentRepository->createOrUpdate('Segment' . rand(0, 10000));

    $this->createSubscriberEntity(); // Subscriber without segment

    $subscriberInSegment = $this->createSubscriberEntity();
    $subscriberInSegment->getSegments()->add(
      $this->createSubscriberSegmentEntity($segment, $subscriberInSegment)
    );

    $globallyUnsubscribedSubscriberInSegment = $this->createSubscriberEntity();
    $subscriberInSegment->getSegments()->add(
      $this->createSubscriberSegmentEntity($segment, $globallyUnsubscribedSubscriberInSegment)
    );
    $globallyUnsubscribedSubscriberInSegment->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);

    $unsubscribedSubscriberInSegment = $this->createSubscriberEntity();
    $subscriberSegment = $this->createSubscriberSegmentEntity($segment, $unsubscribedSubscriberInSegment);
    $subscriberSegment->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $subscriberInSegment->getSegments()->add(
      $subscriberSegment
    );
    $this->entityManager->flush();

    $count = $this->repository->getSubscribersCount((int)$segment->getId());
    expect($count)->equals(3);
    $count = $this->repository->getSubscribersCount((int)$segment->getId(), SubscriberEntity::STATUS_SUBSCRIBED);
    expect($count)->equals(1);
    $ids = $this->repository->getSubscriberIdsInSegment((int)$segment->getId());
    expect($ids)->equals([$subscriberInSegment->getId()]);
    $filteredIds = $this->repository->findSubscribersIdsInSegment((int)$segment->getId(), [$subscriberInSegment->getId(), 20, 30]);
    expect($filteredIds)->equals([$subscriberInSegment->getId()]);
    $filteredIds = $this->repository->findSubscribersIdsInSegment((int)$segment->getId(), [$globallyUnsubscribedSubscriberInSegment->getId()]);
    expect($filteredIds)->equals([]);
  }

  public function testItReturnsSubscibersInDynamicSegments() {
    $segment = $this->createDynamicSegmentEntity();

    $wpUserEmail = 'user-role-test1@example.com';
    $this->tester->deleteWordPressUser($wpUserEmail);
    $this->tester->createWordPressUser($wpUserEmail, 'editor');
    $wpUserSubscriber = $this->entityManager
      ->getRepository(SubscriberEntity::class)
      ->findOneBy(['email' => $wpUserEmail]);
    assert($wpUserSubscriber instanceof SubscriberEntity);
    $wpUserSubscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    assert($wpUserSubscriber instanceof SubscriberEntity);
    $subscriberNoList = $this->createSubscriberEntity(); // Subscriber without segment
    $this->entityManager->flush();

    $count = $this->repository->getSubscribersCount((int)$segment->getId());
    expect($count)->equals(1);
    $count = $this->repository->getSubscribersCount((int)$segment->getId(), SubscriberEntity::STATUS_UNSUBSCRIBED);
    expect($count)->equals(0);
    $count = $this->repository->getSubscribersCount((int)$segment->getId(), SubscriberEntity::STATUS_SUBSCRIBED);
    expect($count)->equals(1);
    $ids = $this->repository->getSubscriberIdsInSegment((int)$segment->getId());
    expect($ids)->equals([$wpUserSubscriber->getId()]);
    $filteredIds = $this->repository->findSubscribersIdsInSegment((int)$segment->getId(), [$wpUserSubscriber->getId(), 20, 30]);
    expect($filteredIds)->equals([$wpUserSubscriber->getId()]);
    $filteredIds = $this->repository->findSubscribersIdsInSegment((int)$segment->getId(), [$subscriberNoList->getId()]);
    expect($filteredIds)->equals([]);

    $this->tester->deleteWordPressUser($wpUserEmail);
  }

  public function testGetSubscribersStatisticsCount() {
    $segment = $this->segmentRepository->createOrUpdate('Segment' . rand(0, 10000));
    $this->entityManager->persist($segment);
    $subscribersData = [
      ['status' => SubscriberEntity::STATUS_UNSUBSCRIBED],
      ['status' => SubscriberEntity::STATUS_SUBSCRIBED],
      ['status' => SubscriberEntity::STATUS_UNCONFIRMED],
      ['status' => SubscriberEntity::STATUS_BOUNCED],
    ];
    $subscriberSegments = [];
    $subscribers = [];

    // normal subscribers
    foreach ($subscribersData as $subscriberData) {
      $subscriber = $this->createSubscriberEntity();
      $subscriber->setStatus($subscriberData['status']);
      $subscriberSegments[] = $this->createSubscriberSegmentEntity($segment, $subscriber);
      $subscribers[] = $subscriber;
    }
    $this->entityManager->flush();

    $subscribersCount = $this->repository->getSubscribersStatisticsCount($segment);
    expect($subscribersCount[SubscriberEntity::STATUS_SUBSCRIBED])->equals(1);
    expect($subscribersCount[SubscriberEntity::STATUS_UNSUBSCRIBED])->equals(1);
    expect($subscribersCount[SubscriberEntity::STATUS_UNCONFIRMED])->equals(1);
    expect($subscribersCount[SubscriberEntity::STATUS_BOUNCED])->equals(1);

    // unsubscribed from this particular segment
    foreach ($subscriberSegments as $subscriberSegment) {
      $subscriberSegment->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    }
    $this->entityManager->flush();

    $subscribersCount = $this->repository->getSubscribersStatisticsCount($segment);
    expect($subscribersCount[SubscriberEntity::STATUS_SUBSCRIBED])->equals(0);
    expect($subscribersCount[SubscriberEntity::STATUS_UNSUBSCRIBED])->equals(4);
    expect($subscribersCount[SubscriberEntity::STATUS_UNCONFIRMED])->equals(0);
    expect($subscribersCount[SubscriberEntity::STATUS_BOUNCED])->equals(0);

    // trashed subscribers
    foreach ($subscriberSegments as $subscriberSegment) {
      $subscriberSegment->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    }
    foreach ($subscribers as $subscriber) {
      $subscriber->setDeletedAt(new \DateTimeImmutable());
    }
    $this->entityManager->flush();

    $subscribersCount = $this->repository->getSubscribersStatisticsCount($segment);
    expect($subscribersCount[SubscriberEntity::STATUS_SUBSCRIBED])->equals(0);
    expect($subscribersCount[SubscriberEntity::STATUS_UNSUBSCRIBED])->equals(0);
    expect($subscribersCount[SubscriberEntity::STATUS_UNCONFIRMED])->equals(0);
    expect($subscribersCount[SubscriberEntity::STATUS_BOUNCED])->equals(0);
  }

  public function _after() {
    parent::_after();
    $this->cleanup();
  }

  private function createSubscriberEntity(): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $rand = rand(0, 100000);
    $subscriber->setEmail("john{$rand}@mailpoet.com");
    $subscriber->setFirstName('John');
    $subscriber->setLastName('Doe');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setSource(Source::API);
    $this->entityManager->persist($subscriber);
    return $subscriber;
  }

  private function createSubscriberSegmentEntity(SegmentEntity $segment, SubscriberEntity $subscriber): SubscriberSegmentEntity {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriberSegment);
    return $subscriberSegment;
  }

  private function createDynamicSegmentEntity(): SegmentEntity {
    $segment = new SegmentEntity('Segment' . rand(0, 10000), SegmentEntity::TYPE_DYNAMIC, 'Segment description');
    $dynamicFilter = new DynamicSegmentFilterEntity($segment, [
      'wordpressRole' => 'editor',
      'segmentType' => DynamicSegmentFilterEntity::TYPE_USER_ROLE,
    ]);
    $segment->getDynamicFilters()->add($dynamicFilter);
    $this->entityManager->persist($segment);
    $this->entityManager->persist($dynamicFilter);
    return $segment;
  }

  private function cleanup() {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(DynamicSegmentFilterEntity::class);
  }
}
