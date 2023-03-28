<?php declare(strict_types = 1);

namespace MailPoet\Segments;

use MailPoet\Config\Populator;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Subscribers\Source;

class SegmentsSimpleListRepositoryTest extends \MailPoetTest {
  /** @var SegmentsSimpleListRepository */
  private $segmentsListRepository;

  public function _before(): void {
    parent::_before();
    $segmentRepository = $this->diContainer->get(SegmentsRepository::class);

    // Prepare Segments
    $this->createDynamicSegmentEntityForEditorUsers();
    $defaultSegment = $segmentRepository->createOrUpdate('Segment Default 1' . rand(0, 10000));
    $segmentRepository->createOrUpdate('Segment Default 2' . rand(0, 10000));
    $populator = $this->diContainer->get(Populator::class);
    $populator->up(); // Prepare WooCommerce and WP Users segments
    // Remove synced WP Users
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);

    // Prepare Subscribers
    $wpUserEmail = 'user-role-test1@example.com';
    $this->tester->deleteWordPressUser($wpUserEmail);
    $this->tester->createWordPressUser($wpUserEmail, 'editor');
    $wpUserSubscriber = $this->entityManager
      ->getRepository(SubscriberEntity::class)
      ->findOneBy(['email' => $wpUserEmail]);
    $this->assertInstanceOf(SubscriberEntity::class, $wpUserSubscriber);
    $wpUserSubscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);

    $subscriber1 = $this->createSubscriberEntity();
    $subscriber2 = $this->createSubscriberEntity();
    $subscriber2->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->createSubscriberEntity(); // Subscriber without list
    $this->createSubscriberSegmentEntity($defaultSegment, $subscriber1);
    $this->createSubscriberSegmentEntity($defaultSegment, $subscriber2);
    $this->entityManager->flush();

    $this->segmentsListRepository = $this->diContainer->get(SegmentsSimpleListRepository::class);
  }

  public function testItReturnsCorrectlyFormattedOutput(): void {
    [$list] = $this->segmentsListRepository->getListWithAssociatedSubscribersCounts();
    expect($list['id'])->string();
    expect($list['name'])->string();
    expect($list['type'])->string();
    expect($list['subscribers'])->int();
  }

  public function testItReturnsSegmentsWithSubscribedSubscribersCount(): void {
    $segments = $this->segmentsListRepository->getListWithSubscribedSubscribersCounts();
    expect($segments)->count(5);
    // Default 1
    expect($segments[0]['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    expect($segments[0]['subscribers'])->equals(1);
    // Default 2
    expect($segments[1]['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    expect($segments[1]['subscribers'])->equals(0);
    // Dynamic
    expect($segments[2]['type'])->equals(SegmentEntity::TYPE_DYNAMIC);
    expect($segments[2]['subscribers'])->equals(1);
    // WooCommerce Users Segment
    expect($segments[3]['type'])->equals(SegmentEntity::TYPE_WC_USERS);
    expect($segments[3]['subscribers'])->equals(0);
    // WordPress Users
    expect($segments[4]['type'])->equals(SegmentEntity::TYPE_WP_USERS);
    expect($segments[4]['subscribers'])->equals(1);
  }

  public function testItReturnsSegmentsWithSubscribedSubscribersCountFilteredBySegmentType(): void {
    $segments = $this->segmentsListRepository->getListWithSubscribedSubscribersCounts([SegmentEntity::TYPE_DEFAULT, SegmentEntity::TYPE_WP_USERS]);
    expect($segments)->count(3);
    // Default 1
    expect($segments[0]['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    expect($segments[0]['subscribers'])->equals(1);
    // Default 2
    expect($segments[1]['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    expect($segments[1]['subscribers'])->equals(0);
    // WordPress Users
    expect($segments[2]['type'])->equals(SegmentEntity::TYPE_WP_USERS);
    expect($segments[2]['subscribers'])->equals(1);
  }

  public function testItReturnsSegmentsWithAssociatedSubscribersCount(): void {
    $segments = $this->segmentsListRepository->getListWithAssociatedSubscribersCounts();
    expect($segments)->count(5);
    // Default 1
    expect($segments[0]['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    expect($segments[0]['subscribers'])->equals(2);
    // Default 2
    expect($segments[1]['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    expect($segments[1]['subscribers'])->equals(0);
    // Dynamic
    expect($segments[2]['type'])->equals(SegmentEntity::TYPE_DYNAMIC);
    expect($segments[2]['subscribers'])->equals(1);
    // WooCommerce Users Segment
    expect($segments[3]['type'])->equals(SegmentEntity::TYPE_WC_USERS);
    expect($segments[3]['subscribers'])->equals(0);
    // WordPress Users
    expect($segments[4]['type'])->equals(SegmentEntity::TYPE_WP_USERS);
    expect($segments[4]['subscribers'])->equals(1);
  }

  public function testItCanAddSegmentForSubscribersWithoutList(): void {
    $segments = $this->segmentsListRepository->getListWithAssociatedSubscribersCounts();
    $segments = $this->segmentsListRepository->addVirtualSubscribersWithoutListSegment($segments);
    expect($segments)->count(6);
    expect($segments[5]['type'])->equals(SegmentEntity::TYPE_WITHOUT_LIST);
    expect($segments[5]['id'])->equals('0');
    expect($segments[5]['subscribers'])->equals(1);
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

  private function createDynamicSegmentEntityForEditorUsers(): SegmentEntity {
    $segment = new SegmentEntity('Segment' . rand(0, 10000), SegmentEntity::TYPE_DYNAMIC, 'Segment description');
    $dynamicFilterData = new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_USER_ROLE,
      UserRole::TYPE,
      ['wordpressRole' => 'editor']
    );
    $dynamicFilter = new DynamicSegmentFilterEntity($segment, $dynamicFilterData);
    $segment->getDynamicFilters()->add($dynamicFilter);
    $this->entityManager->persist($segment);
    $this->entityManager->persist($dynamicFilter);
    return $segment;
  }
}
