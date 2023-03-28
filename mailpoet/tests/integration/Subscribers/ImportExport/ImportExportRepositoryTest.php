<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\ImportExport;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscriberCustomFieldRepository;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\DateTime;
use MailPoetVendor\Carbon\Carbon;

class ImportExportRepositoryTest extends \MailPoetTest {
  /** @var ImportExportRepository */
  private $repository;
  /** @var CustomFieldsRepository */
  private $customFieldsRepository;
  /** @var SubscribersRepository */
  private $subscribersRepository;
  /** @var SubscriberCustomFieldRepository */
  private $subscriberCustomFieldRepository;
  /** @var SegmentsRepository */
  private $segmentsRepository;
  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->repository = $this->diContainer->get(ImportExportRepository::class);
    $this->customFieldsRepository = $this->diContainer->get(CustomFieldsRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);
    $this->subscriberCustomFieldRepository = $this->diContainer->get(SubscriberCustomFieldRepository::class);
  }

  public function testItInsertMultipleSubscribers(): void {
    $columns = [
      'email',
      'first_name',
      'last_name',
    ];
    $data = [
      ['user1@export-test.com', 'One', 'User'],
      ['user2@export-test.com', 'Two', 'User'],
    ];
    $count = $this->repository->insertMultiple(
      SubscriberEntity::class,
      $columns,
      $data
    );

    expect($count)->equals(2);
    $subscribers = $this->subscribersRepository->findAll();
    expect($subscribers)->count(2);
    $user1 = $subscribers[0];
    $this->assertInstanceOf(SubscriberEntity::class, $user1);
    expect($user1->getEmail())->equals('user1@export-test.com');
    expect($user1->getFirstName())->equals('One');
    expect($user1->getLastName())->equals('User');
    $user2 = $subscribers[1];
    $this->assertInstanceOf(SubscriberEntity::class, $user2);
    expect($user2->getEmail())->equals('user2@export-test.com');
    expect($user2->getFirstName())->equals('Two');
    expect($user2->getLastName())->equals('User');
  }

  public function testItUpdateMultipleSubscribers(): void {
    $this->createSubscriber('user1@export-test.com', 'One', 'User');
    $this->createSubscriber('user2@export-test.com', 'Two', 'User');
    $columns = [
      'email',
      'first_name',
      'last_name',
    ];
    $data = [
      ['user1@export-test.com', 'OneOne', 'UserOne'],
      ['user2@export-test.com', 'TwoTwo', 'UserTwo'],
    ];
    $updatedAt = Carbon::createFromTimestamp(time());
    $count = $this->repository->updateMultiple(
      SubscriberEntity::class,
      $columns,
      $data,
      $updatedAt
    );

    expect($count)->equals(2);
    $this->entityManager->clear();
    $subscribers = $this->subscribersRepository->findAll();
    expect($subscribers)->count(2);
    $user1 = $subscribers[0];
    $this->assertInstanceOf(SubscriberEntity::class, $user1);
    expect($user1->getEmail())->equals('user1@export-test.com');
    expect($user1->getFirstName())->equals('OneOne');
    expect($user1->getLastName())->equals('UserOne');
    expect($user1->getUpdatedAt())->equals($updatedAt);
    $user2 = $subscribers[1];
    $this->assertInstanceOf(SubscriberEntity::class, $user2);
    expect($user2->getEmail())->equals('user2@export-test.com');
    expect($user2->getFirstName())->equals('TwoTwo');
    expect($user2->getLastName())->equals('UserTwo');
    expect($user2->getUpdatedAt())->equals($updatedAt);
  }

  public function testItInsertMultipleSubscriberCustomFields(): void {
    $subscriber1 = $this->createSubscriber('user1@export-test.com', 'One', 'User');
    $subscriber2 = $this->createSubscriber('user2@export-test.com', 'Two', 'User');
    $customField = $this->createCustomField('age');
    $columns = [
      'subscriber_id',
      'custom_field_id',
      'value',
    ];
    $data = [
      [$subscriber1->getId(), $customField->getId(), 20],
      [$subscriber2->getId(), $customField->getId(), 25],
    ];
    $count = $this->repository->insertMultiple(
      SubscriberCustomFieldEntity::class,
      $columns,
      $data
    );

    expect($count)->equals(2);
    $customFields = $this->subscriberCustomFieldRepository->findAll();
    expect($customFields)->count(2);
    $subscriberCustomField1 = $customFields[0];
    $this->assertInstanceOf(SubscriberCustomFieldEntity::class, $subscriberCustomField1);
    expect($subscriberCustomField1->getSubscriber())->equals($subscriber1);
    expect($subscriberCustomField1->getCustomField())->equals($customField);
    expect($subscriberCustomField1->getValue())->equals('20');
    $subscriberCustomField2 = $customFields[1];
    $this->assertInstanceOf(SubscriberCustomFieldEntity::class, $subscriberCustomField2);
    expect($subscriberCustomField2->getSubscriber())->equals($subscriber2);
    expect($subscriberCustomField2->getCustomField())->equals($customField);
    expect($subscriberCustomField2->getValue())->equals('25');
  }

  public function testItUpdateMultipleSubscriberCustomFields(): void {
    $subscriber1 = $this->createSubscriber('user1@export-test.com', 'One', 'User');
    $subscriber2 = $this->createSubscriber('user2@export-test.com', 'Two', 'User');
    $customField = $this->createCustomField('age');
    $subscriber1CustomField = $this->createSubscriberCustomField($subscriber1, $customField, '0');
    $subscriber2CustomField = $this->createSubscriberCustomField($subscriber2, $customField, '0');

    $columns = [
      'subscriber_id',
      'custom_field_id',
      'value',
    ];
    $data = [
      [$subscriber1->getId(), $customField->getId(), 20],
      [$subscriber2->getId(), $customField->getId(), 25],
    ];
    $updatedAt = Carbon::createFromTimestamp(time());
    $count = $this->repository->updateMultiple(
      SubscriberCustomFieldEntity::class,
      $columns,
      $data,
      $updatedAt
    );

    expect($count)->equals(2);
    $this->entityManager->clear();
    $this->subscribersRepository->findAll();
    $this->customFieldsRepository->findAll();
    $subscriberCustomFields = $this->subscriberCustomFieldRepository->findAll();
    $subscriberCustomField1 = $subscriberCustomFields[0];
    $this->assertInstanceOf(SubscriberCustomFieldEntity::class, $subscriberCustomField1);
    $resultSubscriber1 = $subscriberCustomField1->getSubscriber();
    $this->assertInstanceOf(SubscriberEntity::class, $resultSubscriber1);
    expect($resultSubscriber1->getId())->equals($subscriber1->getId());
    expect($subscriberCustomField1->getCustomField())->equals($customField);
    expect($subscriberCustomField1->getValue())->equals('20');
    $subscriberCustomField2 = $subscriberCustomFields[1];
    $this->assertInstanceOf(SubscriberCustomFieldEntity::class, $subscriberCustomField2);
    $resultSubscriber2 = $subscriberCustomField2->getSubscriber();
    $this->assertInstanceOf(SubscriberEntity::class, $resultSubscriber2);
    expect($resultSubscriber2->getId())->equals($subscriber2->getId());
    expect($subscriberCustomField2->getCustomField())->equals($customField);
    expect($subscriberCustomField2->getValue())->equals('25');
  }

  public function testItGetSubscribersByDefaultSegment(): void {
    $confirmedAt = Carbon::createFromFormat(DateTime::DEFAULT_DATE_TIME_FORMAT, '2021-02-12 12:11:00');
    $this->assertInstanceOf(Carbon::class, $confirmedAt);
    $confirmedIp = '122.122.122.122';
    $subscribedIp = '123.123.123.123';
    $user1 = $this->createSubscriber('user1@export-test.com', 'One', 'User');
    $user1->setConfirmedAt($confirmedAt->toDateTime());
    $user1->setConfirmedIp($confirmedIp);
    $user1->setSubscribedIp($subscribedIp);
    $user2 = $this->createSubscriber('user2@export-test.com', 'Two', 'User');
    $user3 = $this->createSubscriber('user3@export-test.com', 'Three', 'User');
    $segment1 = $this->createSegment('First', SegmentEntity::TYPE_DEFAULT);
    $segment2 = $this->createSegment('Two', SegmentEntity::TYPE_DEFAULT);
    $this->createSubscriberSegment($user1, $segment1, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegment($user2, $segment1, SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->createSubscriberSegment($user3, $segment2, SubscriberEntity::STATUS_SUBSCRIBED);

    $exported = $this->repository->getSubscribersBatchBySegment($segment1, 100);
    expect($exported)->count(2);
    expect($exported[0]['first_name'])->equals('One');
    expect($exported[0]['last_name'])->equals('User');
    expect($exported[0]['email'])->equals('user1@export-test.com');
    expect($exported[0]['segment_name'])->equals('First');
    expect($exported[0]['confirmed_at'])->equals($confirmedAt);
    expect($exported[0]['created_at'])->equals($user1->getCreatedAt());
    expect($exported[0]['confirmed_ip'])->equals($confirmedIp);
    expect($exported[0]['subscribed_ip'])->equals($subscribedIp);
    expect($exported[1]['first_name'])->equals('Two');
    expect($exported[1]['last_name'])->equals('User');
    expect($exported[1]['email'])->equals('user2@export-test.com');
    expect($exported[1]['segment_name'])->equals('First');
  }

  public function testItGetOnlyNodDeletedSubscribersByDefaultSegment(): void {
    $user1 = $this->createSubscriber('user1@export-test.com', 'One', 'User');
    $user2 = $this->createSubscriber('user2@export-test.com', 'Two', 'User');
    $user3 = $this->createSubscriber('user3@export-test.com', 'Three', 'User');
    $segment1 = $this->createSegment('First', SegmentEntity::TYPE_DEFAULT);
    $this->createSubscriberSegment($user1, $segment1, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegment($user2, $segment1, SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->createSubscriberSegment($user3, $segment1, SubscriberEntity::STATUS_SUBSCRIBED);
    $user2->setDeletedAt(new \DateTime());
    $user3->setDeletedAt(new \DateTime());
    $this->subscribersRepository->flush();

    $exported = $this->repository->getSubscribersBatchBySegment($segment1, 100);
    expect($exported)->count(1);
    expect($exported[0]['first_name'])->equals('One');
    expect($exported[0]['last_name'])->equals('User');
    expect($exported[0]['email'])->equals('user1@export-test.com');
    expect($exported[0]['segment_name'])->equals('First');
  }

  public function testItGetSubscribersWithoutSegment(): void {
    $user1 = $this->createSubscriber('user1@export-test.com', 'One', 'User');
    $user2 = $this->createSubscriber('user2@export-test.com', 'Two', 'User');
    $user3 = $this->createSubscriber('user3@export-test.com', 'Three', 'User');
    $user4 = $this->createSubscriber('user4@export-test.com', 'Four', 'User');
    $user5 = $this->createSubscriber('user5@export-test.com', 'Five', 'User');
    $segment1 = $this->createSegment('First', SegmentEntity::TYPE_DEFAULT);
    $segment2 = $this->createSegment('Two', SegmentEntity::TYPE_DEFAULT);
    $segment3 = $this->createSegment('Three', SegmentEntity::TYPE_DEFAULT);
    $segment3->setDeletedAt(new \DateTime());
    $this->entityManager->persist($segment3);
    // Subscribed to segment, shouldn't be exported
    $this->createSubscriberSegment($user1, $segment1, SubscriberEntity::STATUS_SUBSCRIBED);
    // A mix of subscribed and unsubscribed, shouldn't be exported
    $this->createSubscriberSegment($user3, $segment2, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegment($user3, $segment1, SubscriberEntity::STATUS_UNSUBSCRIBED);
    // Unsubscribed from segment, should be exported
    $this->createSubscriberSegment($user2, $segment2, SubscriberEntity::STATUS_UNSUBSCRIBED);
    // Subscribed to trashed segment, should be exported
    $this->createSubscriberSegment($user4, $segment3, SubscriberEntity::STATUS_SUBSCRIBED);
    // User5 is not subscribed to any segment, should be exported

    $exported = $this->repository->getSubscribersBatchBySegment(null, 100);
    expect($exported)->count(3);
    expect($exported[0]['first_name'])->equals('Two');
    expect($exported[0]['last_name'])->equals('User');
    expect($exported[0]['email'])->equals('user2@export-test.com');
    expect($exported[0]['segment_name'])->equals('Not In Segment');
    expect($exported[1]['first_name'])->equals('Four');
    expect($exported[1]['last_name'])->equals('User');
    expect($exported[1]['email'])->equals('user4@export-test.com');
    expect($exported[1]['segment_name'])->equals('Not In Segment');
    expect($exported[2]['first_name'])->equals('Five');
    expect($exported[2]['last_name'])->equals('User');
    expect($exported[2]['email'])->equals('user5@export-test.com');
    expect($exported[2]['segment_name'])->equals('Not In Segment');
  }

  public function testItGetSubscribersByDynamicSegment(): void {
    $this->tester->createWordPressUser('user1@export-test.com', 'administrator');
    $this->tester->createWordPressUser('user2@export-test.com', 'editor');
    $this->tester->createWordPressUser('user3@export-test.com', 'editor');
    $user4 = $this->createSubscriber('user4@export-test.com', 'Four', 'User');
    $user5 = $this->createSubscriber('user5@export-test.com', 'Five', 'User');
    $segment1 = $this->createSegment('First', SegmentEntity::TYPE_DEFAULT);
    $segment2 = $this->createSegment('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC);
    $this->createDynamicSegmentFilter(
      $segment2,
      DynamicSegmentFilterData::TYPE_USER_ROLE,
      UserRole::TYPE,
      ['wordpressRole' => 'editor']
    );
    $this->createSubscriberSegment($user4, $segment1, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegment($user5, $segment1, SubscriberEntity::STATUS_SUBSCRIBED);

    $this->entityManager->clear();
    $segment2 = $this->segmentsRepository->findOneById($segment2->getId());
    $this->assertInstanceOf(SegmentEntity::class, $segment2);
    $exported = $this->repository->getSubscribersBatchBySegment($segment2, 100);
    expect($exported)->count(2);
    expect($exported[0]['email'])->equals('user2@export-test.com');
    expect($exported[0]['segment_name'])->equals('Dynamic Segment');
    expect($exported[1]['email'])->equals('user3@export-test.com');
    expect($exported[1]['segment_name'])->equals('Dynamic Segment');
  }

  /**
   * Test for https://mailpoet.atlassian.net/browse/MAILPOET-3900. Tries to make sure that
   * subscribers are returned only once for a given dynamic segment. Before, subscribers could be
   * returned multiple times if they had subscribed to other segments.
   */
  public function testItDoesntIncludeSubscribersMultipleTimesForDynamicSegments(): void {
    $wpUserId = $this->tester->createWordPressUser('user1@export-test.com', 'editor');
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $wpUserId]);
    $staticSegment = $this->createSegment('First', SegmentEntity::TYPE_DEFAULT);
    $dynamicSegment = $this->createSegment('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC);
    $this->createDynamicSegmentFilter(
      $dynamicSegment,
      DynamicSegmentFilterData::TYPE_USER_ROLE,
      UserRole::TYPE,
      ['wordpressRole' => 'editor']
    );
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->createSubscriberSegment($subscriber, $staticSegment, SubscriberEntity::STATUS_SUBSCRIBED);

    $exported = $this->repository->getSubscribersBatchBySegment($dynamicSegment, 100);
    $this->assertCount(1, $exported);
    $this->assertSame('user1', $exported[0]['first_name']);
  }

  public function testItGetBatchSubscribersMoreTimes(): void {
    $user1 = $this->createSubscriber('user1@export-test.com', 'One', 'User');
    $user2 = $this->createSubscriber('user2@export-test.com', 'Two', 'User');
    $user3 = $this->createSubscriber('user3@export-test.com', 'Three', 'User');
    $user4 = $this->createSubscriber('user4@export-test.com', 'Four', 'User');
    $user5 = $this->createSubscriber('user5@export-test.com', 'Five', 'User');
    $segment1 = $this->createSegment('First', SegmentEntity::TYPE_DEFAULT);
    $segment2 = $this->createSegment('Two', SegmentEntity::TYPE_DEFAULT);
    $this->createSubscriberSegment($user1, $segment1, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegment($user2, $segment1, SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->createSubscriberSegment($user3, $segment2, SubscriberEntity::STATUS_UNCONFIRMED);
    $this->createSubscriberSegment($user4, $segment2, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegment($user5, $segment2, SubscriberEntity::STATUS_UNSUBSCRIBED);

    $offset = 0;
    for ($i = 3; $i <= 5; $i++) {
      $exported = $this->repository->getSubscribersBatchBySegment($segment2, 1, $offset);
      $offset += count($exported);
      expect($exported)->count(1);
      expect($exported[0]['email'])->equals("user{$i}@export-test.com");
      expect($exported[0]['segment_name'])->equals('Two');
    }
  }

  private function createSegment(string $name, string $type): SegmentEntity {
    $segment = new SegmentEntity($name, $type, '');
    $this->segmentsRepository->persist($segment);
    $this->segmentsRepository->flush();
    return $segment;
  }

  private function createSubscriber(string $email, string $firstName, string $lastName): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setFirstName($firstName);
    $subscriber->setLastName($lastName);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createSubscriberSegment(
    SubscriberEntity $subscriber,
    SegmentEntity $segment,
    string $status
  ): SubscriberSegmentEntity {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, $status);
    $this->subscriberSegmentRepository->persist($subscriberSegment);
    $this->subscriberSegmentRepository->flush();
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

  private function createSubscriberCustomField(
    SubscriberEntity $subscriber,
    CustomFieldEntity $customField,
    string $value
  ): SubscriberCustomFieldEntity {
    $subscriberCustomField = new SubscriberCustomFieldEntity($subscriber, $customField, $value);
    $this->entityManager->persist($subscriberCustomField);
    $this->entityManager->flush();
    return $subscriberCustomField;
  }

  private function createDynamicSegmentFilter(
    SegmentEntity $segment,
    string $filterType,
    string $action,
    array $filterData
  ): DynamicSegmentFilterEntity {
    $filter = new DynamicSegmentFilterEntity($segment, new DynamicSegmentFilterData($filterType, $action, $filterData));
    $this->entityManager->persist($filter);
    $this->entityManager->flush();
    return $filter;
  }

  private function cleanWpUsers(): void {
    $emails = [
      'user1@export-test.com',
      'user2@export-test.com',
      'user3@export-test.com',
    ];
    foreach ($emails as $email) {
      $this->tester->deleteWordPressUser($email);
    }
  }

  protected function _after() {
    $this->cleanup();
  }

  private function cleanup() {
    $this->cleanWpUsers();
  }
}
