<?php declare(strict_types = 1);

namespace MailPoet\Segments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
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

  public function testItReturnsSubscribersInDynamicSegments() {
    $segment = $this->createDynamicSegmentEntity();

    $wpUserEmail = 'user-role-test1@example.com';
    $this->tester->deleteWordPressUser($wpUserEmail);
    $this->tester->createWordPressUser($wpUserEmail, 'editor');
    $wpUserSubscriber = $this->entityManager
      ->getRepository(SubscriberEntity::class)
      ->findOneBy(['email' => $wpUserEmail]);
    $this->assertInstanceOf(SubscriberEntity::class, $wpUserSubscriber);
    $wpUserSubscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->assertInstanceOf(SubscriberEntity::class, $wpUserSubscriber);
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

    $this->clearSubscribersCountCache();
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

    $this->clearSubscribersCountCache();
    $subscribersCount = $this->repository->getSubscribersStatisticsCount($segment);
    expect($subscribersCount[SubscriberEntity::STATUS_SUBSCRIBED])->equals(0);
    expect($subscribersCount[SubscriberEntity::STATUS_UNSUBSCRIBED])->equals(0);
    expect($subscribersCount[SubscriberEntity::STATUS_UNCONFIRMED])->equals(0);
    expect($subscribersCount[SubscriberEntity::STATUS_BOUNCED])->equals(0);
  }

  public function testGetSubscribersCountBySegmentIds(): void {
    $segmentOne = $this->segmentRepository->createOrUpdate('Segment' . rand(0, 10000));
    $segmentTwo = $this->segmentRepository->createOrUpdate('Segment' . rand(0, 10000));

    $subscriberOne = $this->createSubscriberEntity();
    $subscriberTwo = $this->createSubscriberEntity();
    $subscriberThree = $this->createSubscriberEntity();

    $this->createSubscriberSegmentEntity($segmentOne, $subscriberOne);
    $this->createSubscriberSegmentEntity($segmentOne, $subscriberTwo);
    $this->createSubscriberSegmentEntity($segmentTwo, $subscriberThree);
    $this->entityManager->flush();

    // two static segments
    $count = $this->repository->getSubscribersCountBySegmentIds([$segmentOne->getId(), $segmentTwo->getId()]);
    expect($count)->equals(3);

    $dynamicSegmentOne = $this->createDynamicSegmentEntity();
    $this->entityManager->flush();

    $wpAuthorOne = 'user-role-editor1@example.com';
    $this->tester->deleteWordPressUser($wpAuthorOne);
    $this->tester->createWordPressUser($wpAuthorOne, 'editor');

    $wpEditorTwo = 'user-role-editor2@example.com';
    $this->tester->deleteWordPressUser($wpEditorTwo);
    $this->tester->createWordPressUser($wpEditorTwo, 'editor');

    // two static segments and one dynamic segment
    $count = $this->repository->getSubscribersCountBySegmentIds([
      $segmentOne->getId(),
      $segmentTwo->getId(),
      $dynamicSegmentOne->getId(),
    ]);
    expect($count)->equals(5);

    $dynamicSegmentTwo = $this->createDynamicSegmentEntity('author');
    $this->entityManager->flush();

    $wpAuthorOne = 'user-role-author1@example.com';
    $this->tester->deleteWordPressUser($wpAuthorOne);
    $this->tester->createWordPressUser($wpAuthorOne, 'author');

    // two dynamic segments
    $count = $this->repository->getSubscribersCountBySegmentIds([
      $dynamicSegmentOne->getId(),
      $dynamicSegmentTwo->getId(),
    ]);
    expect($count)->equals(3);

    // all four segments
    $count = $this->repository->getSubscribersCountBySegmentIds([
      $segmentOne->getId(),
      $segmentTwo->getId(),
      $dynamicSegmentOne->getId(),
      $dynamicSegmentTwo->getId(),
    ]);
    expect($count)->equals(6);
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

  private function createDynamicSegmentEntity(string $role = 'editor'): SegmentEntity {
    $segment = new SegmentEntity('Segment' . rand(0, 10000), SegmentEntity::TYPE_DYNAMIC, 'Segment description');
    $filterData = new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_USER_ROLE,
      UserRole::TYPE,
      ['wordpressRole' => $role]
    );
    $dynamicFilter = new DynamicSegmentFilterEntity($segment, $filterData);
    $segment->getDynamicFilters()->add($dynamicFilter);
    $this->entityManager->persist($segment);
    $this->entityManager->persist($dynamicFilter);
    return $segment;
  }

  private function cleanup() {
    $this->clearSubscribersCountCache();
  }
}
