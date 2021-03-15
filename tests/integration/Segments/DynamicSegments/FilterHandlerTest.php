<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;

class FilterHandlerTest extends \MailPoetTest {

  /** @var FilterHandler */
  private $filterHandler;

  public function _before() {
    $this->cleanWpUsers();
    $this->filterHandler = $this->diContainer->get(FilterHandler::class);
    $id = $this->tester->createWordPressUser('user-role-test1@example.com', 'editor');
    $this->tester->generateSubscriber([
      'email' => 'user-role-test1@example.com',
      'wp_user_id' => $id,
    ]);
    $id = $this->tester->createWordPressUser('user-role-test2@example.com', 'administrator');
    $this->tester->generateSubscriber([
      'email' => 'user-role-test2@example.com',
      'wp_user_id' => $id,
    ]);
    $id = $this->tester->createWordPressUser('user-role-test3@example.com', 'editor');
    $this->tester->generateSubscriber([
      'email' => 'user-role-test3@example.com',
      'wp_user_id' => $id,
    ]);
  }

  public function testItAppliesFilter() {
    $segment = $this->getSegment('editor');
    $queryBuilder = $this->filterHandler->apply($this->getQueryBuilder(), $segment);
    $result = $queryBuilder->execute()->fetchAll();
    expect($result)->count(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    expect($subscriber1->getEmail())->equals('user-role-test1@example.com');
    expect($subscriber2->getEmail())->equals('user-role-test3@example.com');
  }

  public function testItAppliesTwoFilters() {
    $segment = $this->getSegment('editor');
    $filter = new DynamicSegmentFilterData([
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => 'administrator',
    ]);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $filter);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    $queryBuilder = $this->filterHandler->apply($this->getQueryBuilder(), $segment);
    $result = $queryBuilder->execute()->fetchAll();
    expect($result)->count(3);
  }

  private function getSegment(string $role): SegmentEntity {
    $filter = new DynamicSegmentFilterData([
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => $role,
    ]);
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $filter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    $this->entityManager->persist($segment);
    $this->entityManager->persist($dynamicSegmentFilter);
    $this->entityManager->flush();
    return $segment;
  }

  private function getQueryBuilder() {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id")
      ->from($subscribersTable);
  }

  public function _after() {
    $this->cleanWpUsers();
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SegmentEntity::class);
  }

  private function cleanWpUsers() {
    $emails = ['user-role-test1@example.com', 'user-role-test2@example.com', 'user-role-test3@example.com'];
    foreach ($emails as $email) {
      $this->tester->deleteWordPressUser($email);
    }
  }
}
