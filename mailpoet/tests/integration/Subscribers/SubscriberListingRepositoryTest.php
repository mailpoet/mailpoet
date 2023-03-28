<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Segments\DynamicSegments\FilterHandler;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Test\DataFactories\Tag;
use MailPoetVendor\Carbon\Carbon;

class SubscriberListingRepositoryTest extends \MailPoetTest {

  /** @var SegmentsRepository */
  private $segmentRepository;

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
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->repository = new SubscriberListingRepository(
      $this->entityManager,
      $this->diContainer->get(FilterHandler::class),
      $this->diContainer->get(SegmentSubscribersRepository::class),
      $this->diContainer->get(SubscribersCountsController::class)
    );
  }

  public function testItBuildsFilters() {
    $tag = (new Tag())
      ->withName('My Tag')
      ->create();

    (new SubscriberFactory()) // subscriber without a list with a tag
      ->withTags([$tag])
      ->create();
    $subscriberWithDeletedList = $this->createSubscriberEntity();
    $deletedList = $this->segmentRepository->createOrUpdate('Segment 1');
    $deletedList->setDeletedAt(new \DateTimeImmutable());
    $this->createSubscriberSegmentEntity($deletedList, $subscriberWithDeletedList);

    $subscriberUnsubscribedFromAList = $this->createSubscriberEntity();
    $list = $this->segmentRepository->createOrUpdate('Segment 2');
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
    expect($filters['segment'][2]['label'])->equals('Segment 2 (2)');
    expect($filters['tag'])->count(2);
    expect($filters['tag'][0]['label'])->equals('All Tags');
    expect($filters['tag'][1]['label'])->equals('My Tag (1)');
  }

  public function testItBuildsGroups() {
    $list = $this->segmentRepository->createOrUpdate('Segment 3');

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

  public function testLoadAllSubscribers() {
    $this->createSubscriberEntity(); // subscriber without a list

    $list = $this->segmentRepository->createOrUpdate('Segment 4');
    $subscriberUnsubscribedFromAList = $this->createSubscriberEntity();
    $subscriberSegment = $this->createSubscriberSegmentEntity($list, $subscriberUnsubscribedFromAList);
    $subscriberSegment->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);

    $regularSubscriber = $this->createSubscriberEntity();
    $regularSubscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegmentEntity($list, $regularSubscriber);

    $unsubscribed = $this->createSubscriberEntity();
    $unsubscribed->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);

    $unconfirmed = $this->createSubscriberEntity();
    $unconfirmed->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);

    $inactive = $this->createSubscriberEntity();
    $inactive->setStatus(SubscriberEntity::STATUS_INACTIVE);

    $bounced = $this->createSubscriberEntity();
    $bounced->setStatus(SubscriberEntity::STATUS_BOUNCED);

    $this->entityManager->flush();

    $data = $this->repository->getData($this->getListingDefinition());
    expect(count($data))->equals(7);
  }

  public function testLoadSubscribersInDefaultSegment() {
    $list = $this->segmentRepository->createOrUpdate('Segment 5');
    $subscriberUnsubscribedFromAList = $this->createSubscriberEntity();
    $subscriberSegment = $this->createSubscriberSegmentEntity($list, $subscriberUnsubscribedFromAList);
    $subscriberSegment->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);

    $this->createSubscriberEntity(); // subscriber without a list

    $regularSubscriber = $this->createSubscriberEntity();
    $regularSubscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegmentEntity($list, $regularSubscriber);

    $this->entityManager->flush();

    $this->listingData['filter'] = ['segment' => $list->getId()];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    expect(count($data))->equals(2);
    expect($data[0]->getEmail())->equals($subscriberUnsubscribedFromAList->getEmail());
    expect($data[1]->getEmail())->equals($regularSubscriber->getEmail());
    $this->listingData['sort_by'] = '';
  }

  public function testLoadSubscribersInDynamicSegment() {
    $wpUserEmail = 'user-role-test1@example.com';
    $this->tester->deleteWordPressUser($wpUserEmail);
    $this->tester->createWordPressUser($wpUserEmail, 'editor');
    $list = $this->createDynamicSegmentEntity();

    $this->createSubscriberEntity(); // subscriber without a list
    $this->entityManager->flush();

    $this->listingData['filter'] = ['segment' => $list->getId()];
    $data = $this->repository->getData($this->getListingDefinition());
    expect(count($data))->equals(1);
    expect($data[0]->getEmail())->equals($wpUserEmail);
    $this->tester->deleteWordPressUser($wpUserEmail);
  }

  public function testReturnsCorrectCountForSubscribersInDynamicSegment() {
    $wpUserEmail1 = 'user-role-test1@example.com';
    $wpUserEmail2 = 'user-role-test2@example.com';
    $wpUserEmail3 = 'user-role-test3@example.com';
    $this->tester->deleteWordPressUser($wpUserEmail1);
    $this->tester->deleteWordPressUser($wpUserEmail2);
    $this->tester->deleteWordPressUser($wpUserEmail3);
    $this->tester->createWordPressUser($wpUserEmail1, 'editor');
    $this->tester->createWordPressUser($wpUserEmail2, 'editor');
    $this->tester->createWordPressUser($wpUserEmail3, 'editor');
    $list = $this->createDynamicSegmentEntity();
    $this->entityManager->flush();

    $this->listingData['filter'] = ['segment' => $list->getId()];
    $this->listingData['limit'] = 2;
    $this->listingData['offset'] = 2;
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    expect(count($data))->equals(1);
    expect($data[0]->getEmail())->equals($wpUserEmail3);
    $count = $this->repository->getCount($this->getListingDefinition());
    expect($count)->equals(3);
    $this->tester->deleteWordPressUser($wpUserEmail1);
    $this->tester->deleteWordPressUser($wpUserEmail2);
    $this->tester->deleteWordPressUser($wpUserEmail3);
    $this->listingData['limit'] = 20;
    $this->listingData['offset'] = 0;
  }

  public function testSearchForSubscribersInDynamicSegment() {
    $wpUserEmail1 = 'user-role-test1@example.com';
    $wpUserEmail2 = 'user-role-test2@example.com';
    $this->tester->deleteWordPressUser($wpUserEmail1);
    $this->tester->deleteWordPressUser($wpUserEmail2);
    $this->tester->createWordPressUser($wpUserEmail1, 'editor');
    $this->tester->createWordPressUser($wpUserEmail2, 'editor');
    $list = $this->createDynamicSegmentEntity();
    $this->entityManager->flush();

    $this->listingData['filter'] = ['segment' => $list->getId()];
    $this->listingData['search'] = 'user-role-test2';
    $data = $this->repository->getData($this->getListingDefinition());
    expect(count($data))->equals(1);
    expect($data[0]->getEmail())->equals($wpUserEmail2);
    $count = $this->repository->getCount($this->getListingDefinition());
    expect($count)->equals(1); // Count should be affected by search
    $this->tester->deleteWordPressUser($wpUserEmail1);
    $this->tester->deleteWordPressUser($wpUserEmail2);
    $this->listingData['search'] = '';
  }

  public function testLoadSubscribersWithoutSegment() {
    $list = $this->segmentRepository->createOrUpdate('Segment 6');
    $regularSubscriber = $this->createSubscriberEntity();
    $regularSubscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegmentEntity($list, $regularSubscriber);

    $deletedList = $this->segmentRepository->createOrUpdate('Segment 7');
    $deletedList->setDeletedAt(new \DateTimeImmutable());
    $subscriberOnDeletedList = $this->createSubscriberEntity();
    $subscriberOnDeletedList->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegmentEntity($deletedList, $subscriberOnDeletedList);

    $subscriberInBothLists = $this->createSubscriberEntity();
    $subscriberInBothLists->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegmentEntity($list, $subscriberInBothLists);
    $this->createSubscriberSegmentEntity($deletedList, $subscriberInBothLists);

    $subscriberWithoutList = $this->createSubscriberEntity();

    $this->entityManager->flush();

    $this->listingData['filter'] = ['segment' => SubscriberListingRepository::FILTER_WITHOUT_LIST];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    expect(count($data))->equals(2);
    expect($data[0]->getEmail())->equals($subscriberOnDeletedList->getEmail());
    expect($data[1]->getEmail())->equals($subscriberWithoutList->getEmail());
    $this->listingData['sort_by'] = '';
  }

  public function testFilterSubscribersByUpdatedAt() {
    $subscriber1 = (new Subscriber())
      ->withUpdatedAt(new Carbon('2022-10-10 12:00:00'))
      ->create();
    $subscriber2 = (new Subscriber())
      ->withUpdatedAt(new Carbon('2022-10-11 12:00:00'))
      ->create();
    $subscriber3 = (new Subscriber())
      ->withUpdatedAt(new Carbon('2022-10-12 12:00:00'))
      ->create();

    $this->listingData['filter'] = ['minUpdatedAt' => new Carbon('2022-10-11 12:00:00')];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    expect(count($data))->equals(2);
    expect($data[0]->getEmail())->equals($subscriber2->getEmail());
    expect($data[1]->getEmail())->equals($subscriber3->getEmail());
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

  private function createDynamicSegmentEntity(): SegmentEntity {
    $segment = new SegmentEntity('Segment' . rand(0, 10000), SegmentEntity::TYPE_DYNAMIC, 'Segment description');
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, UserRole::TYPE, [
      'wordpressRole' => 'editor',
    ]);
    $dynamicFilter = new DynamicSegmentFilterEntity($segment, $filterData);
    $segment->getDynamicFilters()->add($dynamicFilter);
    $this->entityManager->persist($segment);
    $this->entityManager->persist($dynamicFilter);
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
