<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Listing\ListingDefinition;

class SubscriberListingRepositoryTest extends \MailPoetTest {

  /** @var SubscriberListingRepository */
  private $repository;

  private $listingData = [
    'params' => [
      0 => '',
    ],
    'offset' => 0,
    'limit' => 20,
    'group' => '',
    'search' => '',
    'sort_by' => '',
    'sort_order' => '',
    'selection' => [],
    'filter' => [],
  ];

  public function _before() {
    $this->repository = new SubscriberListingRepository($this->entityManager);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
  }

  public function testItBuildsFilters() {
    $this->createSubscriberEntity(); // subscriber without a list
    $subscriberWithDeletedList = $this->createSubscriberEntity();
    $deletedList = $this->createSegmentEntity();
    $deletedList->setDeletedAt(new \DateTimeImmutable());
    $this->createSubscriberSegmentEntity($deletedList, $subscriberWithDeletedList);

    $subscriberUnsubscribedFromAList = $this->createSubscriberEntity();
    $list = $this->createSegmentEntity();
    $subscriberSegment = $this->createSubscriberSegmentEntity($list, $subscriberUnsubscribedFromAList);
    $subscriberSegment->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);

    $deletedSubscriber = $this->createSubscriberEntity();
    $deletedSubscriber->setDeletedAt(new \DateTimeImmutable());
    $this->createSubscriberSegmentEntity($list, $deletedSubscriber);

    $regularSubscriber = $this->createSubscriberEntity();
    $regularSubscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegmentEntity($list, $regularSubscriber);

    $this->entityManager->flush();

    $filters = $this->repository->getFilters($this->getListingDefinition());
    expect($filters['segment'])->count(3);
    expect($filters['segment'][0]['label'])->equals('All Lists');
    expect($filters['segment'][1]['label'])->equals('Subscribers without a list (3)');
    expect($filters['segment'][2]['label'])->endsWith('(2)');
  }

  public function testItBuildsGroups() {
    $list = $this->createSegmentEntity();

    $this->createSubscriberEntity(); // subscriber without a list

    $subscriberUnsubscribedFromAList = $this->createSubscriberEntity();
    $subscriberSegment = $this->createSubscriberSegmentEntity($list, $subscriberUnsubscribedFromAList);
    $subscriberSegment->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);

    $regularSubscriber = $this->createSubscriberEntity();
    $regularSubscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegmentEntity($list, $regularSubscriber);

    $deletedSubscriber = $this->createSubscriberEntity();
    $deletedSubscriber->setDeletedAt(new \DateTimeImmutable());
    $this->createSubscriberSegmentEntity($list, $deletedSubscriber);

    $unsubscribed = $this->createSubscriberEntity();
    $unsubscribed->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);

    $unconfirmed = $this->createSubscriberEntity();
    $unconfirmed->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);

    $inactive = $this->createSubscriberEntity();
    $inactive->setStatus(SubscriberEntity::STATUS_INACTIVE);

    $bounced = $this->createSubscriberEntity();
    $bounced->setStatus(SubscriberEntity::STATUS_BOUNCED);

    $this->entityManager->flush();

    $groups = $this->repository->getGroups($this->getListingDefinition());
    expect($groups['0']['name'])->equals('all');
    expect($groups['0']['count'])->equals(7); // bounced + inactive + unconfirmed + unsubscribed + regular + unsub from a list + without a list

    expect($groups['1']['name'])->equals('subscribed');
    expect($groups['1']['count'])->equals(3);// without a list + unsub form a list + regular

    expect($groups['2']['name'])->equals('unconfirmed');
    expect($groups['2']['count'])->equals(1);

    expect($groups['3']['name'])->equals('unsubscribed');
    expect($groups['3']['count'])->equals(1);

    expect($groups['4']['name'])->equals('inactive');
    expect($groups['4']['count'])->equals(1);

    expect($groups['5']['name'])->equals('bounced');
    expect($groups['5']['count'])->equals(1);

    expect($groups['6']['name'])->equals('trash');
    expect($groups['6']['count'])->equals(1);
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

  private function getListingDefinition(): ListingDefinition {
    return new ListingDefinition(
      $this->listingData['group'],
      $this->listingData['filter'],
      $this->listingData['search'],
      $this->listingData['params'],
      $this->listingData['sort_by'],
      $this->listingData['sort_order'],
      $this->listingData['offset'],
      $this->listingData['limit'],
      $this->listingData['selection']
    );
  }
}
