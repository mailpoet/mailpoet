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
    verify($filters['segment'])->arrayCount(3);
    verify($filters['segment'][0]['label'])->equals('All Lists');
    verify($filters['segment'][1]['label'])->equals('Subscribers without a list (3)');
    verify($filters['segment'][2]['label'])->equals('Segment 2 (2)');
    verify($filters['tag'])->arrayCount(2);
    verify($filters['tag'][0]['label'])->equals('All Tags');
    verify($filters['tag'][1]['label'])->equals('My Tag (1)');
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
    verify($groups['0']['name'])->equals('all');
    verify($groups['0']['count'])->equals(7); // bounced + inactive + unconfirmed + unsubscribed + regular + unsub from a list + without a list

    verify($groups['1']['name'])->equals('subscribed');
    verify($groups['1']['count'])->equals(3);// without a list + unsub form a list + regular

    verify($groups['2']['name'])->equals('unconfirmed');
    verify($groups['2']['count'])->equals(1);

    verify($groups['3']['name'])->equals('unsubscribed');
    verify($groups['3']['count'])->equals(1);

    verify($groups['4']['name'])->equals('inactive');
    verify($groups['4']['count'])->equals(1);

    verify($groups['5']['name'])->equals('bounced');
    verify($groups['5']['count'])->equals(1);

    verify($groups['6']['name'])->equals('trash');
    verify($groups['6']['count'])->equals(1);
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
    verify(count($data))->equals(7);
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
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($subscriberUnsubscribedFromAList->getEmail());
    verify($data[1]->getEmail())->equals($regularSubscriber->getEmail());
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
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($wpUserEmail);
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
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($wpUserEmail3);
    $count = $this->repository->getCount($this->getListingDefinition());
    verify($count)->equals(3);
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
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($wpUserEmail2);
    $count = $this->repository->getCount($this->getListingDefinition());
    verify($count)->equals(1); // Count should be affected by search
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
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($subscriberOnDeletedList->getEmail());
    verify($data[1]->getEmail())->equals($subscriberWithoutList->getEmail());
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
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($subscriber2->getEmail());
    verify($data[1]->getEmail())->equals($subscriber3->getEmail());
  }

  public function testFilterSubscribersByCreatedAtFrom() {
    $subscriber1 = (new Subscriber())
      ->withCreatedAt(new Carbon('2022-10-10 12:00:00'))
      ->create();
    $subscriber2 = (new Subscriber())
      ->withCreatedAt(new Carbon('2022-10-11 12:00:00'))
      ->create();
    $subscriber3 = (new Subscriber())
      ->withCreatedAt(new Carbon('2022-10-12 12:00:00'))
      ->create();

    $this->listingData['filter'] = ['createdAtFrom' => '2022-10-11 12:00:00'];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($subscriber2->getEmail());
    verify($data[1]->getEmail())->equals($subscriber3->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByCreatedAtTo() {
    $subscriber1 = (new Subscriber())
      ->withCreatedAt(new Carbon('2022-10-10 12:00:00'))
      ->create();
    $subscriber2 = (new Subscriber())
      ->withCreatedAt(new Carbon('2022-10-11 12:00:00'))
      ->create();
    $subscriber3 = (new Subscriber())
      ->withCreatedAt(new Carbon('2022-10-12 12:00:00'))
      ->create();

    $this->listingData['filter'] = ['createdAtTo' => '2022-10-11 12:00:00'];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($subscriber1->getEmail());
    verify($data[1]->getEmail())->equals($subscriber2->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByCreatedAtRange() {
    $subscriber1 = (new Subscriber())
      ->withCreatedAt(new Carbon('2022-10-10 12:00:00'))
      ->create();
    $subscriber2 = (new Subscriber())
      ->withCreatedAt(new Carbon('2022-10-11 12:00:00'))
      ->create();
    $subscriber3 = (new Subscriber())
      ->withCreatedAt(new Carbon('2022-10-12 12:00:00'))
      ->create();
    $subscriber4 = (new Subscriber())
      ->withCreatedAt(new Carbon('2022-10-13 12:00:00'))
      ->create();

    $this->listingData['filter'] = [
      'createdAtFrom' => '2022-10-11 12:00:00',
      'createdAtTo' => '2022-10-12 12:00:00',
    ];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($subscriber2->getEmail());
    verify($data[1]->getEmail())->equals($subscriber3->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByCreatedAtWithInvalidDate() {
    $subscriber1 = (new Subscriber())
      ->withCreatedAt(new Carbon('2022-10-10 12:00:00'))
      ->create();
    $subscriber2 = (new Subscriber())
      ->withCreatedAt(new Carbon('2022-10-11 12:00:00'))
      ->create();

    // Test with invalid createdAtFrom - should be ignored
    $this->listingData['filter'] = ['createdAtFrom' => 'invalid-date'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2); // All subscribers returned

    // Test with invalid createdAtTo - should be ignored
    $this->listingData['filter'] = ['createdAtTo' => 'not-a-date'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2); // All subscribers returned

    $this->listingData['filter'] = [];
  }

  public function testLoadSubscribersInDefaultSegmentConsideringSubscriberStatusPerSegmentAndNotGlobally() {
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
    $this->listingData['group'] = SubscriberEntity::STATUS_SUBSCRIBED;
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($regularSubscriber->getEmail());
    $this->listingData['group'] = SubscriberEntity::STATUS_UNSUBSCRIBED;
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($subscriberUnsubscribedFromAList->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['group'] = '';
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

  public function testFilterSubscribersByStatusIncludeWithSingleStatus() {
    $subscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $unsubscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $inactive = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_INACTIVE)
      ->create();

    $this->listingData['filter'] = ['statusInclude' => SubscriberEntity::STATUS_SUBSCRIBED];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($subscribed->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByStatusIncludeWithMultipleStatuses() {
    $subscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $unsubscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $inactive = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_INACTIVE)
      ->create();
    $bounced = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_BOUNCED)
      ->create();

    $this->listingData['filter'] = ['statusInclude' => [
      SubscriberEntity::STATUS_SUBSCRIBED,
      SubscriberEntity::STATUS_INACTIVE,
    ]];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($subscribed->getEmail());
    verify($data[1]->getEmail())->equals($inactive->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByStatusIncludeWithInvalidStatus() {
    $subscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $unsubscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();

    // Invalid status should be filtered out, resulting in no filter applied
    $this->listingData['filter'] = ['statusInclude' => ['invalid_status']];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2); // All subscribers returned

    // Empty array should also result in no filter applied
    $this->listingData['filter'] = ['statusInclude' => []];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2); // All subscribers returned

    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByStatusIncludeWithMixedValidAndInvalidStatuses() {
    $subscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $unsubscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $inactive = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_INACTIVE)
      ->create();

    // Invalid statuses should be filtered out, only valid ones should be used
    $this->listingData['filter'] = ['statusInclude' => [
      SubscriberEntity::STATUS_SUBSCRIBED,
      'invalid_status',
      SubscriberEntity::STATUS_INACTIVE,
      123, // non-string value should be filtered out
    ]];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($subscribed->getEmail());
    verify($data[1]->getEmail())->equals($inactive->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByStatusExcludeWithSingleStatus() {
    $subscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $unsubscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $inactive = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_INACTIVE)
      ->create();

    $this->listingData['filter'] = ['statusExclude' => SubscriberEntity::STATUS_SUBSCRIBED];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($unsubscribed->getEmail());
    verify($data[1]->getEmail())->equals($inactive->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByStatusExcludeWithMultipleStatuses() {
    $subscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $unsubscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $inactive = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_INACTIVE)
      ->create();
    $bounced = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_BOUNCED)
      ->create();

    $this->listingData['filter'] = ['statusExclude' => [
      SubscriberEntity::STATUS_SUBSCRIBED,
      SubscriberEntity::STATUS_BOUNCED,
    ]];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($unsubscribed->getEmail());
    verify($data[1]->getEmail())->equals($inactive->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByStatusExcludeWithInvalidStatus() {
    $subscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $unsubscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();

    // Invalid status should be filtered out, resulting in no filter applied
    $this->listingData['filter'] = ['statusExclude' => ['invalid_status']];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2); // All subscribers returned

    // Empty array should also result in no filter applied
    $this->listingData['filter'] = ['statusExclude' => []];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2); // All subscribers returned

    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByStatusExcludeWithMixedValidAndInvalidStatuses() {
    $subscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $unsubscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $inactive = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_INACTIVE)
      ->create();
    $bounced = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_BOUNCED)
      ->create();

    // Invalid statuses should be filtered out, only valid ones should be used
    $this->listingData['filter'] = ['statusExclude' => [
      SubscriberEntity::STATUS_SUBSCRIBED,
      'invalid_status',
      SubscriberEntity::STATUS_BOUNCED,
      456, // non-string value should be filtered out
    ]];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($unsubscribed->getEmail());
    verify($data[1]->getEmail())->equals($inactive->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByBothStatusIncludeAndExclude() {
    $subscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $unsubscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $inactive = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_INACTIVE)
      ->create();
    $bounced = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_BOUNCED)
      ->create();
    $unconfirmed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();

    // Include subscribed, unsubscribed, and inactive, but exclude inactive
    // Result should be only subscribed and unsubscribed
    $this->listingData['filter'] = [
      'statusInclude' => [
        SubscriberEntity::STATUS_SUBSCRIBED,
        SubscriberEntity::STATUS_UNSUBSCRIBED,
        SubscriberEntity::STATUS_INACTIVE,
      ],
      'statusExclude' => [SubscriberEntity::STATUS_INACTIVE],
    ];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($subscribed->getEmail());
    verify($data[1]->getEmail())->equals($unsubscribed->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByStatusIncludeAsStringInsteadOfArray() {
    $subscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $unsubscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();

    // Test that single string value is converted to array
    $this->listingData['filter'] = ['statusInclude' => SubscriberEntity::STATUS_UNSUBSCRIBED];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($unsubscribed->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByStatusExcludeAsStringInsteadOfArray() {
    $subscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $unsubscribed = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();

    // Test that single string value is converted to array
    $this->listingData['filter'] = ['statusExclude' => SubscriberEntity::STATUS_UNSUBSCRIBED];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($subscribed->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testStatusFiltersWorkWithOtherFilters() {
    $list = $this->segmentRepository->createOrUpdate('Segment 8');

    $subscribedInList = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$list])
      ->create();
    $unsubscribedInList = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->withSegments([$list])
      ->create();
    $subscribedNotInList = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    // Filter by segment AND statusInclude
    $this->listingData['filter'] = [
      'segment' => $list->getId(),
      'statusInclude' => SubscriberEntity::STATUS_SUBSCRIBED,
    ];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($subscribedInList->getEmail());
    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByEngagementScoreCategories() {
    $unknownScore = (new Subscriber())->create(); // No engagement score set (null)
    $lowScore = (new Subscriber())->withEngagementScore(10)->create();
    $goodScore = (new Subscriber())->withEngagementScore(35)->create();
    $excellentScore = (new Subscriber())->withEngagementScore(75)->create();

    $this->listingData['sort_by'] = 'id';

    // Test unknown category
    $this->listingData['filter'] = ['engagementScoreInclude' => 'unknown'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($unknownScore->getEmail());

    // Test low category
    $this->listingData['filter'] = ['engagementScoreInclude' => 'low'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($lowScore->getEmail());

    // Test good category
    $this->listingData['filter'] = ['engagementScoreInclude' => 'good'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($goodScore->getEmail());

    // Test excellent category
    $this->listingData['filter'] = ['engagementScoreInclude' => 'excellent'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($excellentScore->getEmail());

    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByEngagementScoreBoundaryValues() {
    // Test boundary values: low < 20, good >= 20 AND < 50, excellent >= 50
    $score0 = (new Subscriber())->withEngagementScore(0)->create();
    $score19 = (new Subscriber())->withEngagementScore(19)->create();
    $score20 = (new Subscriber())->withEngagementScore(20)->create();
    $score49 = (new Subscriber())->withEngagementScore(49)->create();
    $score50 = (new Subscriber())->withEngagementScore(50)->create();
    $score100 = (new Subscriber())->withEngagementScore(100)->create();

    // Low: < 20 (should include 0, 19)
    $this->listingData['filter'] = ['engagementScoreInclude' => 'low'];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($score0->getEmail());
    verify($data[1]->getEmail())->equals($score19->getEmail());

    // Good: >= 20 AND < 50 (should include 20, 49)
    $this->listingData['filter'] = ['engagementScoreInclude' => 'good'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($score20->getEmail());
    verify($data[1]->getEmail())->equals($score49->getEmail());

    // Excellent: >= 50 (should include 50, 100)
    $this->listingData['filter'] = ['engagementScoreInclude' => 'excellent'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($score50->getEmail());
    verify($data[1]->getEmail())->equals($score100->getEmail());

    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByMultipleEngagementScores() {
    $unknownScore = (new Subscriber())->create();
    $lowScore = (new Subscriber())->withEngagementScore(10)->create();
    $goodScore = (new Subscriber())->withEngagementScore(35)->create();
    $excellentScore = (new Subscriber())->withEngagementScore(75)->create();

    $this->listingData['sort_by'] = 'id';

    // Filter by unknown and excellent
    $this->listingData['filter'] = ['engagementScoreInclude' => ['unknown', 'excellent']];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($unknownScore->getEmail());
    verify($data[1]->getEmail())->equals($excellentScore->getEmail());

    // Filter by low and good
    $this->listingData['filter'] = ['engagementScoreInclude' => ['low', 'good']];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($lowScore->getEmail());
    verify($data[1]->getEmail())->equals($goodScore->getEmail());

    // Filter by all categories
    $this->listingData['filter'] = ['engagementScoreInclude' => ['unknown', 'low', 'good', 'excellent']];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(4);

    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByEngagementScoreWithInvalidValue() {
    (new Subscriber())->create();
    (new Subscriber())->withEngagementScore(10)->create();

    // Invalid value should not match any condition
    $this->listingData['filter'] = ['engagementScoreInclude' => ['invalid_score']];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2); // All subscribers returned, because invalid value is ignored

    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByEngagementScoreCombinedWithOtherFilters() {
    $list = $this->segmentRepository->createOrUpdate('Segment for engagement test');

    $unknownInList = (new Subscriber())
      ->withSegments([$list])
      ->create();
    $lowInList = (new Subscriber())
      ->withEngagementScore(10)
      ->withSegments([$list])
      ->create();
    $excellentInList = (new Subscriber())
      ->withEngagementScore(75)
      ->withSegments([$list])
      ->create();
    $excellentNotInList = (new Subscriber())
      ->withEngagementScore(80)
      ->create();

    // Filter by segment AND engagement score
    $this->listingData['filter'] = [
      'segment' => $list->getId(),
      'engagementScoreInclude' => 'excellent',
    ];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($excellentInList->getEmail());

    // Filter by segment AND multiple engagement scores
    $this->listingData['filter'] = [
      'segment' => $list->getId(),
      'engagementScoreInclude' => ['unknown', 'low'],
    ];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($unknownInList->getEmail());
    verify($data[1]->getEmail())->equals($lowInList->getEmail());

    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByEngagementScoreCombinedWithStatusFilter() {
    $subscribedLow = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withEngagementScore(10)
      ->create();
    $unsubscribedLow = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->withEngagementScore(15)
      ->create();
    $subscribedExcellent = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withEngagementScore(75)
      ->create();

    // Filter by status AND engagement score
    $this->listingData['filter'] = [
      'statusInclude' => SubscriberEntity::STATUS_SUBSCRIBED,
      'engagementScoreInclude' => 'low',
    ];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($subscribedLow->getEmail());

    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByEngagementScoreExcludeCategories() {
    $unknownScore = (new Subscriber())->create(); // No engagement score set (null)
    $lowScore = (new Subscriber())->withEngagementScore(10)->create();
    $goodScore = (new Subscriber())->withEngagementScore(35)->create();
    $excellentScore = (new Subscriber())->withEngagementScore(75)->create();

    $this->listingData['sort_by'] = 'id';

    // Exclude unknown category - should return low, good, excellent
    $this->listingData['filter'] = ['engagementScoreExclude' => 'unknown'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(3);
    verify($data[0]->getEmail())->equals($lowScore->getEmail());
    verify($data[1]->getEmail())->equals($goodScore->getEmail());
    verify($data[2]->getEmail())->equals($excellentScore->getEmail());

    // Exclude low category - should return unknown, good, excellent
    $this->listingData['filter'] = ['engagementScoreExclude' => 'low'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(3);
    verify($data[0]->getEmail())->equals($unknownScore->getEmail());
    verify($data[1]->getEmail())->equals($goodScore->getEmail());
    verify($data[2]->getEmail())->equals($excellentScore->getEmail());

    // Exclude good category - should return unknown, low, excellent
    $this->listingData['filter'] = ['engagementScoreExclude' => 'good'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(3);
    verify($data[0]->getEmail())->equals($unknownScore->getEmail());
    verify($data[1]->getEmail())->equals($lowScore->getEmail());
    verify($data[2]->getEmail())->equals($excellentScore->getEmail());

    // Exclude excellent category - should return unknown, low, good
    $this->listingData['filter'] = ['engagementScoreExclude' => 'excellent'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(3);
    verify($data[0]->getEmail())->equals($unknownScore->getEmail());
    verify($data[1]->getEmail())->equals($lowScore->getEmail());
    verify($data[2]->getEmail())->equals($goodScore->getEmail());

    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByEngagementScoreExcludeBoundaryValues() {
    // Test boundary values: low < 20, good >= 20 AND < 50, excellent >= 50
    $score0 = (new Subscriber())->withEngagementScore(0)->create();
    $score19 = (new Subscriber())->withEngagementScore(19)->create();
    $score20 = (new Subscriber())->withEngagementScore(20)->create();
    $score49 = (new Subscriber())->withEngagementScore(49)->create();
    $score50 = (new Subscriber())->withEngagementScore(50)->create();
    $score100 = (new Subscriber())->withEngagementScore(100)->create();

    $this->listingData['sort_by'] = 'id';

    // Exclude low (< 20): should exclude 0, 19 -> return 20, 49, 50, 100
    $this->listingData['filter'] = ['engagementScoreExclude' => 'low'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(4);
    verify($data[0]->getEmail())->equals($score20->getEmail());
    verify($data[1]->getEmail())->equals($score49->getEmail());
    verify($data[2]->getEmail())->equals($score50->getEmail());
    verify($data[3]->getEmail())->equals($score100->getEmail());

    // Exclude good (>= 20 AND < 50): should exclude 20, 49 -> return 0, 19, 50, 100
    $this->listingData['filter'] = ['engagementScoreExclude' => 'good'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(4);
    verify($data[0]->getEmail())->equals($score0->getEmail());
    verify($data[1]->getEmail())->equals($score19->getEmail());
    verify($data[2]->getEmail())->equals($score50->getEmail());
    verify($data[3]->getEmail())->equals($score100->getEmail());

    // Exclude excellent (>= 50): should exclude 50, 100 -> return 0, 19, 20, 49
    $this->listingData['filter'] = ['engagementScoreExclude' => 'excellent'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(4);
    verify($data[0]->getEmail())->equals($score0->getEmail());
    verify($data[1]->getEmail())->equals($score19->getEmail());
    verify($data[2]->getEmail())->equals($score20->getEmail());
    verify($data[3]->getEmail())->equals($score49->getEmail());

    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByMultipleEngagementScoreExcludes() {
    $unknownScore = (new Subscriber())->create();
    $lowScore = (new Subscriber())->withEngagementScore(10)->create();
    $goodScore = (new Subscriber())->withEngagementScore(35)->create();
    $excellentScore = (new Subscriber())->withEngagementScore(75)->create();

    $this->listingData['sort_by'] = 'id';

    // Exclude unknown and low - should return good, excellent
    $this->listingData['filter'] = ['engagementScoreExclude' => ['unknown', 'low']];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($goodScore->getEmail());
    verify($data[1]->getEmail())->equals($excellentScore->getEmail());

    // Exclude good and excellent - should return unknown, low
    $this->listingData['filter'] = ['engagementScoreExclude' => ['good', 'excellent']];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($unknownScore->getEmail());
    verify($data[1]->getEmail())->equals($lowScore->getEmail());

    // Exclude all categories - should return nothing
    $this->listingData['filter'] = ['engagementScoreExclude' => ['unknown', 'low', 'good', 'excellent']];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(0);

    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByBothEngagementScoreIncludeAndExclude() {
    $unknownScore = (new Subscriber())->create();
    $lowScore = (new Subscriber())->withEngagementScore(10)->create();
    $goodScore = (new Subscriber())->withEngagementScore(35)->create();
    $excellentScore = (new Subscriber())->withEngagementScore(75)->create();

    $this->listingData['sort_by'] = 'id';

    // Include low and good, but exclude low - should return only good
    $this->listingData['filter'] = [
      'engagementScoreInclude' => ['low', 'good'],
      'engagementScoreExclude' => 'low',
    ];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($goodScore->getEmail());

    // Include all, exclude unknown and excellent - should return low, good
    $this->listingData['filter'] = [
      'engagementScoreInclude' => ['unknown', 'low', 'good', 'excellent'],
      'engagementScoreExclude' => ['unknown', 'excellent'],
    ];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($lowScore->getEmail());
    verify($data[1]->getEmail())->equals($goodScore->getEmail());

    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByEngagementScoreExcludeCombinedWithOtherFilters() {
    $list = $this->segmentRepository->createOrUpdate('Segment for exclude test');

    $unknownInList = (new Subscriber())
      ->withSegments([$list])
      ->create();
    $lowInList = (new Subscriber())
      ->withEngagementScore(10)
      ->withSegments([$list])
      ->create();
    $excellentInList = (new Subscriber())
      ->withEngagementScore(75)
      ->withSegments([$list])
      ->create();
    $excellentNotInList = (new Subscriber())
      ->withEngagementScore(80)
      ->create();

    // Filter by segment AND exclude excellent engagement score
    $this->listingData['filter'] = [
      'segment' => $list->getId(),
      'engagementScoreExclude' => 'excellent',
    ];
    $this->listingData['sort_by'] = 'id';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($unknownInList->getEmail());
    verify($data[1]->getEmail())->equals($lowInList->getEmail());

    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
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
