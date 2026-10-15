<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Segments\DynamicSegments\FilterFactory;
use MailPoet\Segments\DynamicSegments\FilterHandler;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction as EmailActionFilter;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberTextField;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\SegmentDependencyValidator;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\StatisticsNewsletters;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Test\DataFactories\Tag;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Logging\DebugStack;

class SubscriberListingRepositoryTest extends \MailPoetTest {

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var SubscriberListingRepository */
  private $repository;

  /** @var int */
  private static $subscriberEmailSequence = 0;

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
      $this->diContainer->get(SubscribersCountsController::class),
      $this->diContainer->get(SegmentDependencyValidator::class)
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

  public function testItCountsGroupsRespectingSearch() {
    $aliceSubscribed = $this->createSubscriberEntity();
    $aliceSubscribed->setEmail('alice-group-search-1@example.com');
    $aliceSubscribed->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);

    $aliceUnconfirmed = $this->createSubscriberEntity();
    $aliceUnconfirmed->setEmail('alice-group-search-2@example.com');
    $aliceUnconfirmed->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);

    $bob = $this->createSubscriberEntity();
    $bob->setEmail('bob-group-search@example.com');
    $bob->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);

    $this->entityManager->flush();

    $this->listingData['search'] = 'alice-group-search';
    $groups = $this->repository->getGroups($this->getListingDefinition());
    $this->listingData['search'] = '';

    $byName = array_column($groups, 'count', 'name');
    // Only the two "alice" subscribers match the search; bob is excluded from
    // every bucket, proving the grouped count applies the search.
    verify($byName['all'])->equals(2);
    verify($byName['subscribed'])->equals(1);
    verify($byName['unconfirmed'])->equals(1);
    verify($byName['unsubscribed'])->equals(0);
  }

  public function testItCountsGroupsForStaticSegmentUsingPerListStatus() {
    $segment = $this->segmentRepository->createOrUpdate('Group Static Segment');

    // Globally subscribed, but unsubscribed from THIS list.
    $unsubscribedFromList = $this->createSubscriberEntity();
    $unsubscribedFromList->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $segmentLink = $this->createSubscriberSegmentEntity($segment, $unsubscribedFromList);
    $segmentLink->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);

    // Subscribed to the list.
    $subscribedToList = $this->createSubscriberEntity();
    $subscribedToList->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegmentEntity($segment, $subscribedToList);

    $this->entityManager->flush();

    $this->listingData['filter'] = ['segment' => $segment->getId()];
    $groups = $this->repository->getGroups($this->getListingDefinition());
    $this->listingData['filter'] = [];

    $byName = array_column($groups, 'count', 'name');
    // Per-list status (fallback path): the globally-subscribed member that left
    // this list counts as unsubscribed, not subscribed.
    verify($byName['subscribed'])->equals(1);
    verify($byName['unsubscribed'])->equals(1);
  }

  public function testGetCountAndAllTrashGroupsMatchesStandaloneCounts() {
    $segment = $this->segmentRepository->createOrUpdate('Count All Trash Segment');

    $this->createSubscriberEntity(); // subscribed, no list
    $unsubscribed = $this->createSubscriberEntity();
    $unsubscribed->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $unconfirmed = $this->createSubscriberEntity();
    $unconfirmed->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $trashed = $this->createSubscriberEntity();
    $trashed->setDeletedAt(new \DateTimeImmutable());

    $memberSubscribed = $this->createSubscriberEntity();
    $this->createSubscriberSegmentEntity($segment, $memberSubscribed);
    $memberLeftList = $this->createSubscriberEntity();
    $leftLink = $this->createSubscriberSegmentEntity($segment, $memberLeftList);
    $leftLink->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);

    $dynamicSegment = $this->createDynamicSegment([[DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTextField::EMAIL, ['operator' => 'startsWith', 'value' => 'cg-dyn-']]]);
    $countTag = (new Tag())->withName('Count Tag')->create();
    (new Subscriber())->withEmail('cg-dyn-1@example.com')->withTags([$countTag])->create();
    (new Subscriber())->withEmail('cg-dyn-2@example.com')->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->withTags([$countTag])->create();
    (new Subscriber())->withEmail('cg-dyn-3@example.com')->create();

    $this->entityManager->flush();

    $dynamicFilter = ['segment_in' => [$dynamicSegment->getId()], 'tag_in' => [$countTag->getId()]];
    $scenarios = [
      ['group' => 'all', 'filter' => []],
      ['group' => 'all', 'filter' => $dynamicFilter],
      ['group' => SubscriberEntity::STATUS_UNSUBSCRIBED, 'filter' => $dynamicFilter],
      ['group' => 'trash', 'filter' => $dynamicFilter],
      ['group' => 'all', 'filter' => ['segment_not_in' => [$dynamicSegment->getId()]]],
      ['group' => SubscriberEntity::STATUS_SUBSCRIBED, 'filter' => []],
      ['group' => 'trash', 'filter' => []],
      ['group' => 'all', 'filter' => ['segment' => $segment->getId()]],
      ['group' => SubscriberEntity::STATUS_SUBSCRIBED, 'filter' => ['segment' => $segment->getId()]],
      ['group' => 'all', 'filter' => ['tag_in' => [$countTag->getId()]]],
    ];

    foreach ($scenarios as $scenario) {
      $this->listingData['group'] = $scenario['group'];
      $this->listingData['filter'] = $scenario['filter'];
      $definition = $this->getListingDefinition();
      $this->listingData['group'] = 'all';
      $all = $this->repository->getCount($this->getListingDefinition());
      $this->listingData['group'] = 'trash';
      $trash = $this->repository->getCount($this->getListingDefinition());

      $result = $this->repository->getCountAndAllTrashGroups($definition);

      verify($result['count'])->equals($this->repository->getCount($definition));
      verify($result['groups'])->equals([
        ['name' => 'all', 'label' => 'All', 'count' => $all],
        ['name' => 'trash', 'label' => 'Trash', 'count' => $trash],
      ]);
    }

    $this->listingData['group'] = 'all';
    $this->listingData['filter'] = $dynamicFilter;
    verify($this->repository->getCountAndAllTrashGroups($this->getListingDefinition())['count'])->equals(2);
    $this->listingData['filter'] = ['segment_not_in' => [$dynamicSegment->getId()]];
    verify($this->repository->getCountAndAllTrashGroups($this->getListingDefinition())['count'])->equals(5);

    $this->listingData['group'] = '';
    $this->listingData['filter'] = [];
  }

  public function testGetCountAndAllTrashGroupsRunsAtMostTwoCountQueries() {
    $dynamicSegment = $this->createDynamicSegment([[DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTextField::EMAIL, ['operator' => 'startsWith', 'value' => 'cq-dyn-']]]);
    $tag = (new Tag())->withName('Count Queries Tag')->create();
    (new Subscriber())->withEmail('cq-dyn-1@example.com')->withTags([$tag])->create();
    (new Subscriber())->withEmail('cq-dyn-2@example.com')->withTags([$tag])->withDeletedAt(new Carbon())->create();
    $this->entityManager->flush();

    $logger = new DebugStack();
    $configuration = $this->entityManager->getConnection()->getConfiguration();
    $previousLogger = $configuration->getSQLLogger();
    $configuration->setSQLLogger($logger);
    try {
      foreach (['all', 'trash'] as $group) {
        $logger->queries = [];
        $result = $this->repository->getCountAndAllTrashGroups(new ListingDefinition(
          $group,
          ['segment_in' => [$dynamicSegment->getId()], 'tag_in' => [$tag->getId()]]
        ));
        $countQueries = array_filter($logger->queries, function(array $query): bool {
          return stripos($query['sql'], 'COUNT(') !== false;
        });
        verify(count($countQueries))->lessThanOrEqual(2);
        verify(array_column($result['groups'], 'count', 'name'))->equals(['all' => 1, 'trash' => 1]);
      }
    } finally {
      $configuration->setSQLLogger($previousLogger);
    }
  }

  public function testGetGroupsForAnEmptyDefinitionKeepsEveryStatusGroup() {
    $groups = array_column($this->repository->getGroups(new ListingDefinition()), 'name');
    verify($groups)->equals(['all', 'subscribed', 'unconfirmed', 'unsubscribed', 'inactive', 'bounced', 'trash']);
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

  public function testItSearchesSubscribersByEmailSubstring() {
    $matchingSubscriber = (new Subscriber())
      ->withEmail('john.doe@frankenstein.com')
      ->withFirstName('John')
      ->withLastName('Doe')
      ->create();
    (new Subscriber())
      ->withEmail('jane.doe@example.com')
      ->withFirstName('Jane')
      ->withLastName('Doe')
      ->create();

    $this->listingData['search'] = 'frank';
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($matchingSubscriber->getEmail());
    $count = $this->repository->getCount($this->getListingDefinition());
    verify($count)->equals(1);
    $this->listingData['search'] = '';
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

  public function testItSortsByCreatedAtWithIdTiebreaker() {
    // First-inserted (lowest id) but the newest created_at.
    $newestSameSecond = $this->createSubscriberEntity();
    $newestSameSecond->setCreatedAt(new \DateTimeImmutable('2020-01-01 00:00:00'));

    // Second-inserted (higher id) sharing the newest created_at second.
    $newestHigherId = $this->createSubscriberEntity();
    $newestHigherId->setCreatedAt(new \DateTimeImmutable('2020-01-01 00:00:00'));

    // Third-inserted (highest id) but the oldest created_at.
    $oldest = $this->createSubscriberEntity();
    $oldest->setCreatedAt(new \DateTimeImmutable('2010-01-01 00:00:00'));

    $this->entityManager->flush();

    $this->listingData['sort_by'] = 'created_at';
    $this->listingData['sort_order'] = 'desc';
    $data = $this->repository->getData($this->getListingDefinition());
    $this->listingData['sort_by'] = '';
    $this->listingData['sort_order'] = '';

    verify(count($data))->equals(3);
    // Real created_at order: the oldest date sorts last despite its highest id.
    // Within the tied newest second, id desc breaks the tie deterministically.
    verify($data[0]->getEmail())->equals($newestHigherId->getEmail());
    verify($data[1]->getEmail())->equals($newestSameSecond->getEmail());
    verify($data[2]->getEmail())->equals($oldest->getEmail());
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

  public function testItUsesFallbackSortingForDynamicSegmentWhenDefinitionContainsSqlFragments() {
    $wpUserEmail = 'user-role-test1@example.com';
    $this->tester->deleteWordPressUser($wpUserEmail);
    $this->tester->createWordPressUser($wpUserEmail, 'editor');
    $list = $this->createDynamicSegmentEntity();
    $this->entityManager->flush();

    $definition = new ListingDefinition(
      null,
      ['segment' => $list->getId()],
      null,
      [],
      'id, not_a_column',
      'asc, not_a_column',
      0,
      20
    );

    $data = $this->repository->getData($definition);
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
    self::$subscriberEmailSequence++;
    $subscriber->setEmail(
      sprintf('subscriber-listing-repository-%d@mailpoet.com', self::$subscriberEmailSequence)
    );
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

  public function testUnknownEngagementScoreIncludesMissingScoresWithRecentSends() {
    $untracked = (new Subscriber())->create();
    $this->createSentEmails($untracked, 5, Carbon::now()->subDays(10));
    $dormant = (new Subscriber())->create();
    $this->createSentEmails($dormant, 3, Carbon::now()->subMonths(13));
    $scored = (new Subscriber())->withEngagementScore(35)->create();

    $this->listingData['sort_by'] = 'id';
    $this->listingData['filter'] = ['engagementScoreInclude' => 'unknown'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(array_map(fn($subscriber) => $subscriber->getEmail(), $data))->equals([$untracked->getEmail()]);

    $this->listingData['filter'] = ['engagementScoreExclude' => 'unknown'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(array_map(fn($subscriber) => $subscriber->getEmail(), $data))->equals([$dormant->getEmail(), $scored->getEmail()]);

    $this->listingData['sort_by'] = '';
    $this->listingData['filter'] = [];
  }

  public function testResendQueueUsesTheSameUnknownEngagementScoreGroup() {
    $untracked = (new Subscriber())->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->create();
    $this->createSentEmails($untracked, 5, Carbon::now()->subDays(10));
    $dormant = (new Subscriber())->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->create();
    $this->createSentEmails($dormant, 3, Carbon::now()->subMonths(13));

    $this->listingData['group'] = SubscriberEntity::STATUS_UNCONFIRMED;
    $this->listingData['filter'] = ['engagementScoreInclude' => 'unknown'];
    $included = $this->repository->getConfirmationEmailResendQueueData(
      $this->getListingDefinition(),
      Carbon::now()->subDay(),
      Carbon::now()->subYears(5),
      10,
      100
    );
    $this->listingData['filter'] = ['engagementScoreExclude' => 'unknown'];
    $excluded = $this->repository->getConfirmationEmailResendQueueData(
      $this->getListingDefinition(),
      Carbon::now()->subDay(),
      Carbon::now()->subYears(5),
      10,
      100
    );

    verify($included['queued_ids'])->equals([$untracked->getId()]);
    verify($excluded['queued_ids'])->equals([$dormant->getId()]);
    $this->listingData['group'] = '';
    $this->listingData['filter'] = [];
  }

  public function testFilterSubscribersByEngagementScoreCategories() {
    $unknownScore = (new Subscriber())->create(); // No engagement score set (null)
    $dormantScore = (new Subscriber())->create();
    $this->createSentEmails($dormantScore, 3, Carbon::now()->subMonths(13));
    $lowScore = (new Subscriber())->withEngagementScore(10)->create();
    $goodScore = (new Subscriber())->withEngagementScore(35)->create();
    $excellentScore = (new Subscriber())->withEngagementScore(75)->create();

    $this->listingData['sort_by'] = 'id';

    // Test unknown category
    $this->listingData['filter'] = ['engagementScoreInclude' => 'unknown'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($unknownScore->getEmail());

    // Test dormant category
    $this->listingData['filter'] = ['engagementScoreInclude' => 'dormant'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(1);
    verify($data[0]->getEmail())->equals($dormantScore->getEmail());

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
    $dormantScore = (new Subscriber())->create();
    $this->createSentEmails($dormantScore, 3, Carbon::now()->subMonths(13));
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

    // Filter by dormant and excellent
    $this->listingData['filter'] = ['engagementScoreInclude' => ['dormant', 'excellent']];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($dormantScore->getEmail());
    verify($data[1]->getEmail())->equals($excellentScore->getEmail());

    // Filter by low and good
    $this->listingData['filter'] = ['engagementScoreInclude' => ['low', 'good']];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(2);
    verify($data[0]->getEmail())->equals($lowScore->getEmail());
    verify($data[1]->getEmail())->equals($goodScore->getEmail());

    // Filter by all categories
    $this->listingData['filter'] = ['engagementScoreInclude' => ['unknown', 'dormant', 'low', 'good', 'excellent']];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(5);

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
    $dormantScore = (new Subscriber())->create();
    $this->createSentEmails($dormantScore, 3, Carbon::now()->subMonths(13));
    $lowScore = (new Subscriber())->withEngagementScore(10)->create();
    $goodScore = (new Subscriber())->withEngagementScore(35)->create();
    $excellentScore = (new Subscriber())->withEngagementScore(75)->create();

    $this->listingData['sort_by'] = 'id';

    // Exclude unknown category - should return dormant, low, good, excellent
    $this->listingData['filter'] = ['engagementScoreExclude' => 'unknown'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(4);
    verify($data[0]->getEmail())->equals($dormantScore->getEmail());
    verify($data[1]->getEmail())->equals($lowScore->getEmail());
    verify($data[2]->getEmail())->equals($goodScore->getEmail());
    verify($data[3]->getEmail())->equals($excellentScore->getEmail());

    // Exclude dormant category - should return unknown, low, good, excellent
    $this->listingData['filter'] = ['engagementScoreExclude' => 'dormant'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(4);
    verify($data[0]->getEmail())->equals($unknownScore->getEmail());
    verify($data[1]->getEmail())->equals($lowScore->getEmail());
    verify($data[2]->getEmail())->equals($goodScore->getEmail());
    verify($data[3]->getEmail())->equals($excellentScore->getEmail());

    // Exclude low category - should return unknown, dormant, good, excellent
    $this->listingData['filter'] = ['engagementScoreExclude' => 'low'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(4);
    verify($data[0]->getEmail())->equals($unknownScore->getEmail());
    verify($data[1]->getEmail())->equals($dormantScore->getEmail());
    verify($data[2]->getEmail())->equals($goodScore->getEmail());
    verify($data[3]->getEmail())->equals($excellentScore->getEmail());

    // Exclude good category - should return unknown, dormant, low, excellent
    $this->listingData['filter'] = ['engagementScoreExclude' => 'good'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(4);
    verify($data[0]->getEmail())->equals($unknownScore->getEmail());
    verify($data[1]->getEmail())->equals($dormantScore->getEmail());
    verify($data[2]->getEmail())->equals($lowScore->getEmail());
    verify($data[3]->getEmail())->equals($excellentScore->getEmail());

    // Exclude excellent category - should return unknown, dormant, low, good
    $this->listingData['filter'] = ['engagementScoreExclude' => 'excellent'];
    $data = $this->repository->getData($this->getListingDefinition());
    verify(count($data))->equals(4);
    verify($data[0]->getEmail())->equals($unknownScore->getEmail());
    verify($data[1]->getEmail())->equals($dormantScore->getEmail());
    verify($data[2]->getEmail())->equals($lowScore->getEmail());
    verify($data[3]->getEmail())->equals($goodScore->getEmail());

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

  public function testSegmentInMatchesMembersOfAnyOfTheLists() {
    $listA = (new SegmentFactory())->withName('List A')->create();
    $listB = (new SegmentFactory())->withName('List B')->create();
    $listC = (new SegmentFactory())->withName('List C')->create();
    $inA = (new Subscriber())->withSegments([$listA])->create();
    $inB = (new Subscriber())->withSegments([$listB])->create();
    (new Subscriber())->withSegments([$listC])->create();
    (new Subscriber())->create();

    $ids = $this->getIds(['segment_in' => [$listA->getId(), $listB->getId()]]);

    verify($ids)->equals([$inA->getId(), $inB->getId()]);
  }

  public function testSegmentNotInExcludesMembersOfTheLists() {
    $listA = (new SegmentFactory())->withName('List A')->create();
    $listB = (new SegmentFactory())->withName('List B')->create();
    (new Subscriber())->withSegments([$listA])->create();
    $inB = (new Subscriber())->withSegments([$listB])->create();
    $inBoth = (new Subscriber())->withSegments([$listA, $listB])->create();
    $withoutList = (new Subscriber())->create();

    $ids = $this->getIds(['segment_not_in' => [$listA->getId()]]);

    verify($ids)->equals([$inB->getId(), $withoutList->getId()]);
    $this->assertNotContains($inBoth->getId(), $ids);
  }

  /**
   * @dataProvider segmentsCountColumnStates
   */
  public function testSegmentInWithoutListAndAListIsAnOr(bool $columnReady) {
    $list = (new SegmentFactory())->withName('List A')->create();
    $otherList = (new SegmentFactory())->withName('List B')->create();
    $inList = (new Subscriber())->withSegments([$list])->create();
    $inOtherList = (new Subscriber())->withSegments([$otherList])->create();
    $withoutList = (new Subscriber())->create();
    $this->prepareSegmentsCountColumn($columnReady);

    $ids = $this->getIds(['segment_in' => [SubscriberListingRepository::FILTER_WITHOUT_LIST, $list->getId()]]);

    verify($ids)->equals([$inList->getId(), $withoutList->getId()]);
    $this->assertNotContains($inOtherList->getId(), $ids);
  }

  /**
   * @dataProvider segmentsCountColumnStates
   */
  public function testSegmentNotInWithoutListKeepsOnlySubscribersWithAList(bool $columnReady) {
    $list = (new SegmentFactory())->withName('List A')->create();
    $inList = (new Subscriber())->withSegments([$list])->create();
    $withoutList = (new Subscriber())->create();
    $this->prepareSegmentsCountColumn($columnReady);

    $ids = $this->getIds(['segment_not_in' => [SubscriberListingRepository::FILTER_WITHOUT_LIST]]);

    verify($ids)->equals([$inList->getId()]);
    $this->assertNotContains($withoutList->getId(), $ids);
  }

  public function segmentsCountColumnStates(): array {
    return [
      'column not ready' => [false],
      'column ready' => [true],
    ];
  }

  public function testTagInMatchesSubscribersWithAnyOfTheTags() {
    $tagA = (new Tag())->withName('Tag A')->create();
    $tagB = (new Tag())->withName('Tag B')->create();
    $tagC = (new Tag())->withName('Tag C')->create();
    $withA = (new Subscriber())->withTags([$tagA])->create();
    $withB = (new Subscriber())->withTags([$tagB])->create();
    (new Subscriber())->withTags([$tagC])->create();
    (new Subscriber())->create();

    $ids = $this->getIds(['tag_in' => [$tagA->getId(), $tagB->getId()]]);

    verify($ids)->equals([$withA->getId(), $withB->getId()]);
  }

  public function testTagNotInKeepsSubscribersWithoutAnyTag() {
    $tagA = (new Tag())->withName('Tag A')->create();
    $tagB = (new Tag())->withName('Tag B')->create();
    $withA = (new Subscriber())->withTags([$tagA])->create();
    $withB = (new Subscriber())->withTags([$tagB])->create();
    $withBoth = (new Subscriber())->withTags([$tagA, $tagB])->create();
    $untagged = (new Subscriber())->create();

    $ids = $this->getIds(['tag_not_in' => [$tagA->getId()]]);

    verify($ids)->equals([$withB->getId(), $untagged->getId()]);
    $this->assertNotContains($withA->getId(), $ids);
    $this->assertNotContains($withBoth->getId(), $ids);
  }

  public function testSourceInAndNotIn() {
    $form = (new Subscriber())->withSource(Source::FORM)->create();
    $imported = (new Subscriber())->withSource(Source::IMPORTED)->create();
    $admin = (new Subscriber())->withSource(Source::ADMINISTRATOR)->create();

    verify($this->getIds(['source_in' => [Source::FORM, Source::IMPORTED]]))->equals([$form->getId(), $imported->getId()]);
    verify($this->getIds(['source_not_in' => [Source::FORM, Source::IMPORTED]]))->equals([$admin->getId()]);
    verify($this->getIds(['source_in' => ['not-a-source']]))->equals([$form->getId(), $imported->getId(), $admin->getId()]);
  }

  public function testListingRowsCarryTheSource() {
    (new Subscriber())->withSource(Source::IMPORTED)->create();
    $this->entityManager->clear();

    $rows = $this->repository->getData(new ListingDefinition(null, [], null, [], 'id', 'asc', 0, 10));

    verify($rows)->arrayCount(1);
    verify($rows[0]->getSource())->equals(Source::IMPORTED);
  }

  public function testLegacyScalarFiltersStillFilter() {
    $list = (new SegmentFactory())->withName('List A')->create();
    $tag = (new Tag())->withName('Tag A')->create();
    $match = (new Subscriber())->withSegments([$list])->withTags([$tag])->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    (new Subscriber())->withSegments([$list])->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    (new Subscriber())->withTags([$tag])->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    (new Subscriber())->withSegments([$list])->withTags([$tag])->withStatus(SubscriberEntity::STATUS_INACTIVE)->create();

    $ids = $this->getIds([
      'segment' => (string)$list->getId(),
      'tag' => $tag->getId(),
      'statusInclude' => SubscriberEntity::STATUS_SUBSCRIBED,
    ]);

    verify($ids)->equals([$match->getId()]);
  }

  public function testLegacyScalarSegmentAndTagThatDoNotExistAreIgnored() {
    $first = (new Subscriber())->create();
    $second = (new Subscriber())->create();

    $ids = $this->getIds(['segment' => 999999, 'tag' => 999999]);

    verify($ids)->equals([$first->getId(), $second->getId()]);
  }

  public function testLegacySegmentIsMergedWithSegmentIn() {
    $listA = (new SegmentFactory())->withName('List A')->create();
    $listB = (new SegmentFactory())->withName('List B')->create();
    $inA = (new Subscriber())->withSegments([$listA])->create();
    $inB = (new Subscriber())->withSegments([$listB])->create();
    (new Subscriber())->create();

    $ids = $this->getIds(['segment' => $listA->getId(), 'segment_in' => [$listB->getId()]]);

    verify($ids)->equals([$inA->getId(), $inB->getId()]);
  }

  public function testStatusInUnderOneListUsesTheStatusOfThatList() {
    $list = (new SegmentFactory())->withName('List A')->create();
    $subscribed = (new Subscriber())->withSegments([$list])->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    $unsubscribedFromList = (new Subscriber())->withSegments([$list])->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    $unconfirmed = (new Subscriber())->withSegments([$list])->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->create();
    $unconfirmedAndUnsubscribedFromList = (new Subscriber())->withSegments([$list])->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->create();
    $this->setListStatus($unsubscribedFromList, $list, SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->setListStatus($unconfirmedAndUnsubscribedFromList, $list, SubscriberEntity::STATUS_UNSUBSCRIBED);

    $filters = ['segment_in' => [$list->getId()]];

    verify($this->getIds($filters + ['status_in' => [SubscriberEntity::STATUS_SUBSCRIBED]]))->equals([$subscribed->getId()]);
    verify($this->getIds($filters + ['status_in' => [SubscriberEntity::STATUS_UNSUBSCRIBED]]))
      ->equals([$unsubscribedFromList->getId(), $unconfirmedAndUnsubscribedFromList->getId()]);
    verify($this->getIds($filters + ['status_in' => [SubscriberEntity::STATUS_UNCONFIRMED]]))->equals([$unconfirmed->getId()]);
    verify($this->getIds($filters + ['status_not_in' => [SubscriberEntity::STATUS_SUBSCRIBED]]))
      ->equals([$unsubscribedFromList->getId(), $unconfirmed->getId(), $unconfirmedAndUnsubscribedFromList->getId()]);
  }

  public function testStatusInUnderTwoListsUsesTheGlobalStatus() {
    $listA = (new SegmentFactory())->withName('List A')->create();
    $listB = (new SegmentFactory())->withName('List B')->create();
    $globallySubscribed = (new Subscriber())->withSegments([$listA])->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    $globallyUnsubscribed = (new Subscriber())->withSegments([$listB])->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->create();
    $unsubscribedFromListOnly = (new Subscriber())->withSegments([$listA])->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    $this->setListStatus($unsubscribedFromListOnly, $listA, SubscriberEntity::STATUS_UNSUBSCRIBED);

    $filters = ['segment_in' => [$listA->getId(), $listB->getId()]];

    verify($this->getIds($filters, SubscriberEntity::STATUS_UNSUBSCRIBED))->equals([$globallyUnsubscribed->getId()]);
    verify($this->getIds($filters + ['status_in' => [SubscriberEntity::STATUS_SUBSCRIBED]]))
      ->equals([$globallySubscribed->getId(), $unsubscribedFromListOnly->getId()]);
  }

  public function testResendQueueSupportsSetFiltersAndLegacyFilters() {
    $list = (new SegmentFactory())->withName('List A')->create();
    $otherList = (new SegmentFactory())->withName('List B')->create();
    $tag = (new Tag())->withName('Tag A')->create();
    $otherTag = (new Tag())->withName('Tag B')->create();
    $match = (new Subscriber())->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->withSegments([$list])->withTags([$tag])->create();
    $inOtherList = (new Subscriber())->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->withSegments([$otherList])->withTags([$tag])->create();
    $otherTagged = (new Subscriber())->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->withSegments([$list])->withTags([$otherTag])->create();
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->withSegments([$list])->withTags([$tag])->create();
    $unlisted = (new Subscriber())->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->create();

    $queue = function(array $filters) {
      return $this->repository->getConfirmationEmailResendQueueData(
        new ListingDefinition(SubscriberEntity::STATUS_UNCONFIRMED, $filters, null, [], 'id', 'asc', 0, 100),
        Carbon::now()->subDay(),
        Carbon::now()->subYears(5),
        10,
        100
      )['queued_ids'];
    };

    verify($queue(['tag_in' => [$tag->getId(), $otherTag->getId()], 'segment_not_in' => [$otherList->getId()]]))
      ->equals([$match->getId(), $otherTagged->getId()]);
    verify($queue(['tag_not_in' => [$otherTag->getId()], 'segment_in' => [$list->getId(), $otherList->getId()]]))
      ->equals([$match->getId(), $inOtherList->getId()]);
    verify($queue(['segment' => $list->getId(), 'tag' => $tag->getId()]))->equals([$match->getId()]);
    verify($queue(['segment_in' => [SubscriberListingRepository::FILTER_WITHOUT_LIST]]))->equals([$unlisted->getId()]);
    verify($queue(['segment_in' => [SubscriberListingRepository::FILTER_WITHOUT_LIST, $list->getId()]]))
      ->equals([$match->getId(), $otherTagged->getId(), $unlisted->getId()]);

    // Under one list the status filter uses the status of that list.
    $this->setListStatus($otherTagged, $list, SubscriberEntity::STATUS_UNSUBSCRIBED);
    verify($queue(['segment_in' => [$list->getId()], 'status_in' => [SubscriberEntity::STATUS_UNCONFIRMED]]))->equals([$match->getId()]);
  }

  public function testGetGroupsWithoutFiltersCountsEveryStatus() {
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->create();
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_BOUNCED)->create();
    (new Subscriber())->withDeletedAt(new Carbon())->create();

    $groups = array_column($this->repository->getGroups(new ListingDefinition()), 'count', 'name');

    verify($groups)->equals([
      'all' => 4,
      SubscriberEntity::STATUS_SUBSCRIBED => 2,
      SubscriberEntity::STATUS_UNCONFIRMED => 1,
      SubscriberEntity::STATUS_UNSUBSCRIBED => 0,
      SubscriberEntity::STATUS_INACTIVE => 0,
      SubscriberEntity::STATUS_BOUNCED => 1,
      'trash' => 1,
    ]);
  }

  public function testGetGroupsWithSeveralListsCountsWithTheGlobalStatus() {
    $listA = (new SegmentFactory())->withName('List A')->create();
    $listB = (new SegmentFactory())->withName('List B')->create();
    (new Subscriber())->withSegments([$listA])->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    (new Subscriber())->withSegments([$listB])->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->create();
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();

    $definition = new ListingDefinition(null, ['segment_in' => [$listA->getId(), $listB->getId()]]);
    $groups = array_column($this->repository->getGroups($definition), 'count', 'name');

    verify($groups['all'])->equals(2);
    verify($groups[SubscriberEntity::STATUS_SUBSCRIBED])->equals(1);
    verify($groups[SubscriberEntity::STATUS_UNSUBSCRIBED])->equals(1);
  }

  public function testItBuildsStatusAndSourceFiltersWithGlobalStatusCounts() {
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_BOUNCED)->create();
    $this->diContainer->get(SubscribersCountsController::class)->recalculateGlobalStatusStatisticsCache();

    // The counts are global: the current filters and group do not narrow them.
    $definition = new ListingDefinition(SubscriberEntity::STATUS_BOUNCED, ['source_in' => [Source::FORM]]);
    $filters = $this->repository->getFilters($definition);

    verify(array_column($filters['status'], 'value'))->equals([
      SubscriberEntity::STATUS_SUBSCRIBED,
      SubscriberEntity::STATUS_UNCONFIRMED,
      SubscriberEntity::STATUS_UNSUBSCRIBED,
      SubscriberEntity::STATUS_INACTIVE,
      SubscriberEntity::STATUS_BOUNCED,
    ]);
    verify($filters['status'][0]['label'])->equals('Subscribed (2)');
    verify($filters['status'][4]['label'])->equals('Bounced (1)');
    verify(array_column($filters['source'], 'value'))->equals([
      Source::FORM,
      Source::IMPORTED,
      Source::ADMINISTRATOR,
      Source::API,
      Source::WORDPRESS_USER,
      Source::WORDPRESS_USER_DELETED,
      Source::WOOCOMMERCE_USER,
      Source::WOOCOMMERCE_CHECKOUT,
      Source::UNKNOWN,
    ]);
    verify(array_column($filters['source'], 'label', 'value')[Source::UNKNOWN])->equals('Unknown');
  }

  public function testDynamicSegmentAppliesTheOtherFilters() {
    $dynamic = $this->createEmailSegment('dyn-f-');
    $tag = (new Tag())->withName('Dynamic Tag')->create();
    $tagged = (new Subscriber())->withEmail('dyn-f-tagged@example.com')->withTags([$tag])->create();
    $untagged = (new Subscriber())->withEmail('dyn-f-untagged@example.com')->create();
    $unsubscribed = (new Subscriber())->withEmail('dyn-f-unsub@example.com')->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->withTags([$tag])->create();
    $old = (new Subscriber())->withEmail('dyn-f-old@example.com')->withCreatedAt(new Carbon('2020-01-01 10:00:00'))->create();
    (new Subscriber())->withEmail('other-tagged@example.com')->withTags([$tag])->create();
    $segment = ['segment_in' => [$dynamic->getId()]];

    verify($this->getIds($segment + ['tag_in' => [$tag->getId()]]))->equals([$tagged->getId(), $unsubscribed->getId()]);
    verify($this->getIds($segment + ['tag_not_in' => [$tag->getId()]]))->equals([$untagged->getId(), $old->getId()]);
    verify($this->getIds($segment + ['status_in' => [SubscriberEntity::STATUS_UNSUBSCRIBED]]))->equals([$unsubscribed->getId()]);
    verify($this->getIds($segment + ['status_not_in' => [SubscriberEntity::STATUS_UNSUBSCRIBED]]))
      ->equals([$tagged->getId(), $untagged->getId(), $old->getId()]);
    verify($this->getIds($segment + ['createdAtFrom' => '2021-01-01 00:00:00']))
      ->equals([$tagged->getId(), $untagged->getId(), $unsubscribed->getId()]);
    verify($this->getIds($segment + ['source_in' => [Source::FORM]]))->equals([]);
    verify($this->getIds($segment, SubscriberEntity::STATUS_UNSUBSCRIBED))->equals([$unsubscribed->getId()]);

    $definition = new ListingDefinition(null, $segment, 'untagged', [], 'id', 'asc', 0, 100);
    verify(array_map(function(SubscriberEntity $s) {
      return $s->getId();
    }, $this->repository->getData($definition)))->equals([$untagged->getId()]);
    verify($this->repository->getCount($definition))->equals(1);
  }

  public function testDynamicSegmentWithAnOrGroupAndANoneGroup() {
    $this->repository = $this->buildRepository();
    $orSegment = $this->createDynamicSegment([
      [DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTextField::EMAIL, ['operator' => 'startsWith', 'value' => 'grp-or-a']],
      [DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTextField::EMAIL, ['operator' => 'startsWith', 'value' => 'grp-or-b']],
    ], DynamicSegmentFilterData::CONNECT_TYPE_OR);
    $noneSegment = $this->createDynamicSegment([
      [DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTextField::EMAIL, ['operator' => 'startsWith', 'value' => 'grp-none-x']],
      [DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTextField::EMAIL, ['operator' => 'startsWith', 'value' => 'grp-none-y']],
    ], DynamicSegmentFilterData::CONNECT_TYPE_NONE);
    $orA = (new Subscriber())->withEmail('grp-or-a@example.com')->create();
    $orB = (new Subscriber())->withEmail('grp-or-b@example.com')->create();
    (new Subscriber())->withEmail('grp-or-c@example.com')->create();
    (new Subscriber())->withEmail('grp-or-a-unsub@example.com')->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->create();
    $noneZ = (new Subscriber())->withEmail('grp-none-z@example.com')->create();
    (new Subscriber())->withEmail('grp-none-x@example.com')->create();
    (new Subscriber())->withEmail('grp-none-y@example.com')->create();

    verify($this->getIds(['segment_in' => [$orSegment->getId()], 'status_in' => [SubscriberEntity::STATUS_SUBSCRIBED]]))
      ->equals([$orA->getId(), $orB->getId()]);
    $noneIds = $this->getIds(['segment_in' => [$noneSegment->getId()], 'status_in' => [SubscriberEntity::STATUS_SUBSCRIBED]]);
    verify(in_array($noneZ->getId(), $noneIds, true))->true();
    verify(count(array_intersect($noneIds, [$orA->getId(), $orB->getId()])))->equals(2);
    $emails = array_map(function(SubscriberEntity $s) {
      return $s->getEmail();
    }, $this->repository->getData(new ListingDefinition(null, ['segment_in' => [$noneSegment->getId()]], 'grp-none', [], 'id', 'asc', 0, 100)));
    verify($emails)->equals(['grp-none-z@example.com']);
  }

  public function testMultiSelectMixesStaticAndDynamicSegments() {
    $dynamic = $this->createEmailSegment('mix-dyn-');
    $list = (new SegmentFactory())->withName('Mix List')->create();
    $dynMember = (new Subscriber())->withEmail('mix-dyn-1@example.com')->create();
    $listMember = (new Subscriber())->withEmail('mix-list-1@example.com')->withSegments([$list])->create();
    $both = (new Subscriber())->withEmail('mix-dyn-2@example.com')->withSegments([$list])->create();
    $neither = (new Subscriber())->withEmail('mix-none@example.com')->create();

    verify($this->getIds(['segment_in' => [$list->getId(), $dynamic->getId()]]))
      ->equals([$dynMember->getId(), $listMember->getId(), $both->getId()]);
    verify($this->getIds(['segment_in' => [$dynamic->getId()], 'segment_not_in' => [$list->getId()]]))->equals([$dynMember->getId()]);
    verify($this->getIds(['segment_not_in' => [$dynamic->getId()]]))->equals([$listMember->getId(), $neither->getId()]);
    verify($this->getIds(['segment_not_in' => [$dynamic->getId(), $list->getId()]]))->equals([$neither->getId()]);
    verify($this->getIds(['segment_in' => [SubscriberListingRepository::FILTER_WITHOUT_LIST, $dynamic->getId()]]))
      ->equals([$dynMember->getId(), $both->getId(), $neither->getId()]);
    verify($this->getIds(['segment' => $dynamic->getId(), 'segment_in' => [$list->getId()]]))
      ->equals([$dynMember->getId(), $listMember->getId(), $both->getId()]);
  }

  public function testDynamicSegmentWithMissingPluginFailsClosed() {
    $this->repository = $this->buildRepository(['woocommerce/woocommerce.php']);
    $missing = $this->createDynamicSegment([[DynamicSegmentFilterData::TYPE_WOOCOMMERCE, 'purchasedCategory', ['category_ids' => ['1'], 'operator' => DynamicSegmentFilterData::OPERATOR_ANY]]]);
    $missingInNone = $this->createDynamicSegment([
      [DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTextField::EMAIL, ['operator' => 'startsWith', 'value' => 'miss-']],
      [DynamicSegmentFilterData::TYPE_WOOCOMMERCE, 'purchasedCategory', ['category_ids' => ['1'], 'operator' => DynamicSegmentFilterData::OPERATOR_ANY]],
    ], DynamicSegmentFilterData::CONNECT_TYPE_NONE);
    $list = (new SegmentFactory())->withName('Missing List')->create();
    $member = (new Subscriber())->withEmail('miss-1@example.com')->withSegments([$list])->create();
    (new Subscriber())->withEmail('miss-2@example.com')->create();

    verify($this->getIds(['segment_in' => [$list->getId(), $missing->getId()]]))->equals([$member->getId()]);
    verify($this->getIds(['segment_in' => [$missing->getId()]]))->equals([]);
    verify($this->getIds(['segment_not_in' => [$missing->getId()]]))->equals([]);
    verify($this->getIds(['segment_not_in' => [$missingInNone->getId()]]))->equals([]);
    verify($this->getIds(['segment_in' => [$missingInNone->getId()]]))->equals([]);
    verify($this->getIds(['segment_in' => [$list->getId(), $missingInNone->getId()]]))->equals([$member->getId()]);
  }

  public function testTwoDynamicSegmentsWithTheSameParameterNamesDoNotCollide() {
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $opensData = ['newsletters' => [(int)$newsletter->getId()], 'operator' => DynamicSegmentFilterData::OPERATOR_ANY];
    $humanOpens = $this->createDynamicSegment([[DynamicSegmentFilterData::TYPE_EMAIL, EmailActionFilter::ACTION_OPENED, $opensData]]);
    $machineOpens = $this->createDynamicSegment([[DynamicSegmentFilterData::TYPE_EMAIL, EmailActionFilter::ACTION_MACHINE_OPENED, $opensData]]);
    $human = (new Subscriber())->withEmail('open-human@example.com')->create();
    $machine = (new Subscriber())->withEmail('open-machine@example.com')->create();
    (new Subscriber())->withEmail('open-nobody@example.com')->create();
    (new StatisticsOpens($newsletter, $human))->create();
    (new StatisticsOpens($newsletter, $machine))->withMachineUserAgentType()->create();

    verify($this->getIds(['segment_in' => [$humanOpens->getId()]]))->equals([$human->getId()]);
    verify($this->getIds(['segment_in' => [$machineOpens->getId()]]))->equals([$machine->getId()]);
    verify($this->getIds(['segment_in' => [$humanOpens->getId(), $machineOpens->getId()]]))->equals([$human->getId(), $machine->getId()]);
    verify(count($this->getIds(['segment_not_in' => [$humanOpens->getId(), $machineOpens->getId()]])))->equals(1);
  }

  public function testPagingTheDynamicSegmentOverEqualSortValuesHasNoDuplicatesOrGaps() {
    $dynamic = $this->createEmailSegment('page-');
    $createdAt = new Carbon('2022-05-05 10:00:00');
    $expected = [];
    for ($i = 1; $i <= 5; $i++) {
      $expected[] = (new Subscriber())->withEmail("page-$i@example.com")->withCreatedAt($createdAt)->withEngagementScore(10)->create()->getId();
    }
    foreach (['created_at', 'engagement_score', 'last_subscribed_at'] as $sortBy) {
      $seen = [];
      foreach ([0, 2, 4] as $offset) {
        $rows = $this->repository->getData(new ListingDefinition(null, ['segment_in' => [$dynamic->getId()]], null, [], $sortBy, 'desc', $offset, 2));
        foreach ($rows as $row) {
          $seen[] = $row->getId();
        }
      }
      verify($seen)->equals(array_reverse($expected));
    }
  }

  public function testDynamicSegmentSortsByEngagementScore() {
    $dynamic = $this->createEmailSegment('score-');
    $low = (new Subscriber())->withEmail('score-1@example.com')->withEngagementScore(10)->create();
    $high = (new Subscriber())->withEmail('score-2@example.com')->withEngagementScore(30)->create();
    $mid = (new Subscriber())->withEmail('score-3@example.com')->withEngagementScore(20)->create();
    $rows = $this->repository->getData(new ListingDefinition(null, ['segment_in' => [$dynamic->getId()]], null, [], 'engagement_score', 'desc', 0, 10));
    verify(array_map(function(SubscriberEntity $s) {
      return $s->getId();
    }, $rows))->equals([$high->getId(), $mid->getId(), $low->getId()]);
  }

  public function testResendSelectedCountMatchesTheListingCountUnderOneList() {
    $list = (new SegmentFactory())->withName('Resend Parity')->create();
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->withSegments([$list])->create();
    $leftList = (new Subscriber())->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->withSegments([$list])->create();
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->withSegments([$list])->create();
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->create();
    $this->setListStatus($leftList, $list, SubscriberEntity::STATUS_UNSUBSCRIBED);

    // The resend only ever targets unconfirmed subscribers, so the other
    // statuses select only those that are unconfirmed globally.
    $expectedSelected = [
      SubscriberEntity::STATUS_UNCONFIRMED => 1,
      SubscriberEntity::STATUS_SUBSCRIBED => 0,
      SubscriberEntity::STATUS_UNSUBSCRIBED => 1,
    ];
    foreach ($expectedSelected as $status => $expected) {
      $definitions = [
        new ListingDefinition($status, ['segment_in' => [$list->getId()]], null, [], 'id', 'asc', 0, 100),
        new ListingDefinition(null, ['segment_in' => [$list->getId()], 'status_in' => [$status]], null, [], 'id', 'asc', 0, 100),
      ];
      foreach ($definitions as $definition) {
        $queue = $this->repository->getConfirmationEmailResendQueueData($definition, Carbon::now()->subDay(), Carbon::now()->subYears(5), 10, 100);
        verify($queue['selected_count'])->equals($expected);
        if ($status === SubscriberEntity::STATUS_UNCONFIRMED) {
          verify($queue['selected_count'])->equals($this->repository->getCount($definition));
        }
      }
    }
  }

  public function testResendQueueNeverContainsConfirmedSubscribers() {
    $list = (new SegmentFactory())->withName('Resend Guard')->create();
    $unconfirmed = (new Subscriber())->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->withSegments([$list])->create();
    $subscribed = (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->withSegments([$list])->create();
    $unsubscribed = (new Subscriber())->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->withSegments([$list])->create();

    foreach ([null, 'all'] as $group) {
      foreach ([[], ['status_not_in' => [SubscriberEntity::STATUS_BOUNCED]]] as $extraFilter) {
        $definition = new ListingDefinition($group, ['segment_in' => [$list->getId()]] + $extraFilter, null, [], 'id', 'asc', 0, 100);
        $queue = $this->repository->getConfirmationEmailResendQueueData($definition, Carbon::now()->subDay(), Carbon::now()->subYears(5), 10, 100);
        verify($queue['queued_ids'])->equals([$unconfirmed->getId()]);
      }
    }
    $selection = new ListingDefinition(null, ['segment_in' => [$list->getId()]], null, [], 'id', 'asc', 0, 100, [(int)$unconfirmed->getId(), (int)$subscribed->getId(), (int)$unsubscribed->getId()]);
    $queue = $this->repository->getConfirmationEmailResendQueueData($selection, Carbon::now()->subDay(), Carbon::now()->subYears(5), 10, 100, true);
    verify($queue['queued_ids'])->equals([$unconfirmed->getId()]);
  }

  public function testScoreGtAndLtAreExclusiveAndMinMaxAreInclusive() {
    $low = (new Subscriber())->withEngagementScore(10)->create();
    $edge = (new Subscriber())->withEngagementScore(50)->create();
    $high = (new Subscriber())->withEngagementScore(90)->create();
    $noScore = (new Subscriber())->create();

    verify($this->getIds(['score_gt' => 50]))->equals([$high->getId()]);
    verify($this->getIds(['score_lt' => 50]))->equals([$low->getId()]);
    verify($this->getIds(['score_min' => 50]))->equals([$edge->getId(), $high->getId()]);
    verify($this->getIds(['score_max' => 50]))->equals([$low->getId(), $edge->getId()]);
    verify($this->getIds(['score_min' => 10, 'score_max' => 50]))->equals([$low->getId(), $edge->getId()]);
    verify($this->getIds(['score_gt' => 10, 'score_lt' => 90]))->equals([$edge->getId()]);
    foreach (['score_gt' => -1, 'score_lt' => 1000, 'score_min' => 0, 'score_max' => 1000] as $key => $value) {
      $this->assertNotContains($noScore->getId(), $this->getIds([$key => $value]));
    }
  }

  public function testNonNumericScoreValuesAreIgnored() {
    $a = (new Subscriber())->withEngagementScore(10)->create();
    $b = (new Subscriber())->create();

    verify($this->getIds(['score_gt' => 'abc', 'score_min' => '', 'score_max' => []]))->equals([$a->getId(), $b->getId()]);
    verify($this->getIds(['score_gt' => '5']))->equals([$a->getId()]);
  }

  public function testCreatedFromAndToAreInclusiveWholeDays() {
    $beforeDay = (new Subscriber())->withCreatedAt(new Carbon('2022-10-09 23:59:59'))->create();
    $dayStart = (new Subscriber())->withCreatedAt(new Carbon('2022-10-10 00:00:00'))->create();
    $dayEnd = (new Subscriber())->withCreatedAt(new Carbon('2022-10-10 23:59:59'))->create();
    $afterDay = (new Subscriber())->withCreatedAt(new Carbon('2022-10-11 00:00:00'))->create();

    verify($this->getIds(['created_from' => '2022-10-10', 'created_to' => '2022-10-10']))->equals([$dayStart->getId(), $dayEnd->getId()]);
    verify($this->getIds(['created_from' => '2022-10-10']))->equals([$dayStart->getId(), $dayEnd->getId(), $afterDay->getId()]);
    verify($this->getIds(['created_to' => '2022-10-10']))->equals([$beforeDay->getId(), $dayStart->getId(), $dayEnd->getId()]);
  }

  public function testSubscribedFromAndToAreInclusiveWholeDays() {
    $never = (new Subscriber())->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->create();
    $beforeDay = (new Subscriber())->withLastSubscribedAt(new Carbon('2022-10-09 23:59:59'))->create();
    $dayStart = (new Subscriber())->withLastSubscribedAt(new Carbon('2022-10-10 00:00:00'))->create();
    $dayEnd = (new Subscriber())->withLastSubscribedAt(new Carbon('2022-10-10 23:59:59'))->create();
    $afterDay = (new Subscriber())->withLastSubscribedAt(new Carbon('2022-10-11 00:00:00'))->create();

    verify($this->getIds(['subscribed_from' => '2022-10-10', 'subscribed_to' => '2022-10-10']))->equals([$dayStart->getId(), $dayEnd->getId()]);
    verify($this->getIds(['subscribed_from' => '2022-10-10']))->equals([$dayStart->getId(), $dayEnd->getId(), $afterDay->getId()]);
    verify($this->getIds(['subscribed_to' => '2022-10-10']))->equals([$beforeDay->getId(), $dayStart->getId(), $dayEnd->getId()]);
    $this->assertNotContains($never->getId(), $this->getIds(['subscribed_from' => '2000-01-01', 'subscribed_to' => '2100-01-01']));
  }

  public function testScoreCombinesWithTagAndStatus() {
    $tag = (new Tag())->withName('Score Tag')->create();
    $match = (new Subscriber())->withEngagementScore(70)->withTags([$tag])->create();
    (new Subscriber())->withEngagementScore(30)->withTags([$tag])->create();
    (new Subscriber())->withEngagementScore(70)->create();
    (new Subscriber())->withEngagementScore(70)->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->withTags([$tag])->create();

    $filters = ['score_gt' => 50, 'tag_in' => [$tag->getId()], 'status_in' => [SubscriberEntity::STATUS_SUBSCRIBED]];
    verify($this->getIds($filters))->equals([$match->getId()]);
    verify($this->getIds(['score_gt' => 99] + $filters))->equals([]);
  }

  public function testScoreAndDateFiltersGiveTheSameResultOnTheListAndTheDynamicSegmentPath() {
    $list = (new SegmentFactory())->withName('Parity List')->create();
    $dynamic = $this->createEmailSegment('par-');
    $make = function(string $name, $score, ?string $created, ?string $subscribed) use ($list) {
      $subscriber = (new Subscriber())
        ->withEmail("par-$name@example.com")
        ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
        ->withSegments([$list]);
      if ($score !== null) {
        $subscriber->withEngagementScore($score);
      }
      if ($created) {
        $subscriber->withCreatedAt(new Carbon($created));
      }
      if ($subscribed) {
        $subscriber->withLastSubscribedAt(new Carbon($subscribed));
      }
      return $subscriber->create()->getId();
    };
    $make('a', 50, '2022-10-10 00:00:00', '2022-10-10 23:59:59');
    $make('b', 50.5, '2022-10-10 23:59:59', '2022-10-11 00:00:00');
    $make('c', 10, '2022-10-11 00:00:00', null);
    $make('d', null, '2022-10-09 23:59:59', '2022-10-09 23:59:59');
    $make('e', 90, '2022-10-10 12:00:00', '2022-10-10 00:00:00');

    $cases = [
      ['score_gt' => 50],
      ['score_lt' => 50.5],
      ['score_min' => 50, 'score_max' => 50.5],
      ['score_min' => 0],
      ['created_from' => '2022-10-10', 'created_to' => '2022-10-10'],
      ['created_to' => '2022-10-10'],
      ['subscribed_from' => '2022-10-10', 'subscribed_to' => '2022-10-10'],
      ['subscribed_from' => '2022-10-11'],
      ['score_gt' => 20, 'created_from' => '2022-10-10', 'subscribed_to' => '2022-10-10'],
    ];
    foreach ($cases as $filters) {
      $viaList = $this->getIds(['segment_in' => [$list->getId()]] + $filters);
      $viaDynamic = $this->getIds(['segment_in' => [$dynamic->getId()]] + $filters);
      verify($viaDynamic)->equals($viaList);
      verify($this->resendIds(['segment_in' => [$dynamic->getId()]] + $filters))->equals($viaList);
    }
    verify(count($this->getIds(['segment_in' => [$dynamic->getId()], 'created_from' => '2022-10-10', 'created_to' => '2022-10-10'])))->equals(3);
    verify(count($this->getIds(['segment_in' => [$dynamic->getId()], 'score_gt' => 50])))->equals(2);
  }

  /**
   * @return int[]
   */
  private function resendIds(array $filters): array {
    $definition = new ListingDefinition(null, $filters, null, [], 'id', 'asc', 0, 100);
    return $this->repository->getConfirmationEmailResendQueueData(
      $definition,
      Carbon::now()->subDay(),
      Carbon::now()->subYears(5),
      10,
      100
    )['queued_ids'];
  }

  /**
   * @return int[]
   */
  private function getIds(array $filters, ?string $group = null): array {
    $definition = new ListingDefinition($group, $filters, null, [], 'id', 'asc', 0, 100);
    $rows = $this->repository->getData($definition);
    $ids = array_map(function(SubscriberEntity $subscriber): int {
      return (int)$subscriber->getId();
    }, $rows);
    // getCount must agree with getData for the same definition.
    verify($this->repository->getCount($definition))->equals(count($ids));
    return $ids;
  }

  private function createEmailSegment(string $prefix): SegmentEntity {
    return $this->createDynamicSegment([[DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTextField::EMAIL, ['operator' => 'startsWith', 'value' => $prefix]]]);
  }

  /**
   * @param array<int, array{0: string, 1: string, 2: array<string, mixed>}> $filters [type, action, data]
   */
  private function createDynamicSegment(array $filters, string $connect = DynamicSegmentFilterData::CONNECT_TYPE_AND): SegmentEntity {
    $segment = new SegmentEntity('Dynamic ' . uniqid(), SegmentEntity::TYPE_DYNAMIC, 'Segment description');
    $this->entityManager->persist($segment);
    foreach ($filters as [$type, $action, $data]) {
      $filter = new DynamicSegmentFilterEntity($segment, new DynamicSegmentFilterData($type, $action, $data + ['action' => $action, 'connect' => $connect]));
      $segment->addDynamicFilter($filter);
      $this->entityManager->persist($filter);
    }
    $this->entityManager->flush();
    return $segment;
  }

  /**
   * @param string[] $inactivePlugins
   */
  private function buildRepository(array $inactivePlugins = []): SubscriberListingRepository {
    $wp = $this->make(WPFunctions::class, [
      'isPluginActive' => function(string $plugin) use ($inactivePlugins): bool {
        return !in_array($plugin, $inactivePlugins, true);
      },
    ]);
    $validator = new SegmentDependencyValidator(
      $this->make(SubscribersFeature::class, ['hasValidPremiumKey' => true, 'check' => false]),
      $wp
    );
    return new SubscriberListingRepository(
      $this->entityManager,
      new FilterHandler($this->entityManager, $validator, $this->diContainer->get(FilterFactory::class)),
      $this->diContainer->get(SegmentSubscribersRepository::class),
      $this->diContainer->get(SubscribersCountsController::class),
      $validator
    );
  }

  private function setListStatus(SubscriberEntity $subscriber, SegmentEntity $segment, string $status): void {
    $this->entityManager->clear();
    $subscriberSegment = $this->entityManager->getRepository(SubscriberSegmentEntity::class)->findOneBy([
      'subscriber' => $subscriber->getId(),
      'segment' => $segment->getId(),
    ]);
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberSegment);
    $subscriberSegment->setStatus($status);
    $this->entityManager->flush();
  }

  private function prepareSegmentsCountColumn(bool $ready): void {
    $settings = $this->diContainer->get(SettingsController::class);
    if ($ready) {
      $ids = array_map(function(SubscriberEntity $subscriber): int {
        return (int)$subscriber->getId();
      }, $this->entityManager->getRepository(SubscriberEntity::class)->findAll());
      $this->diContainer->get(SegmentsCountRecalculator::class)->recalculateForSubscribers($ids);
    }
    $settings->set(SegmentSubscribersRepository::BACKFILLED_SETTING_KEY, $ready);
  }

  private function createSentEmails(SubscriberEntity $subscriber, int $count, Carbon $sentAt): void {
    for ($i = 0; $i < $count; $i++) {
      $newsletter = (new Newsletter())->withSendingQueue()->create();
      (new StatisticsNewsletters($newsletter, $subscriber))->withSentAt($sentAt)->create();
    }
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
