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

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(SegmentSubscribersRepository::class);
    $this->cleanup();
  }

  public function testItReturnsOnlySubscribedSubscribersForStaticSegment() {
    $segment = $this->createSegmentEntity();

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

  private function createSegmentEntity(): SegmentEntity {
    $segment = new SegmentEntity('Segment' . rand(0, 10000), SegmentEntity::TYPE_DEFAULT, 'Segment description');
    $this->entityManager->persist($segment);
    return $segment;
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
