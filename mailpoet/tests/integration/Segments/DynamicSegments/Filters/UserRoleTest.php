<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class UserRoleTest extends \MailPoetTest {

  /** @var UserRole */
  private $userRole;

  public function _before(): void {
    global $wpdb;
    $this->userRole = $this->diContainer->get(UserRole::class);
    $this->cleanup();
    // Insert WP users and subscribers are created automatically
    $this->tester->createWordPressUser('user-role-test1@example.com', 'editor');
    $this->tester->createWordPressUser('user-role-test2@example.com', 'administrator');
    $this->tester->createWordPressUser('user-role-test3@example.com', 'editor');
    $this->tester->createWordPressUser('user-role-test4@example.com', 'author');
    $userId = $this->tester->createWordPressUser('user-role-test5@example.com', 'subscriber');
    // some plugins allow setting 2 different roles for a single user, lets emulate that behaviour:
    $this->connection->executeStatement(
      'UPDATE ' . $wpdb->usermeta
      . " SET meta_value='" . serialize(['subscriber' => true, 'merchant' => true]) . "'"
      . " WHERE meta_key='{$wpdb->prefix}capabilities' AND user_id = " . $userId
    );
    $this->tester->createWordPressUser('user-role-test6@example.com', 'subscriber');
  }

  public function testItAppliesFilter(): void {
    $segmentFilter = $this->getSegmentFilter('editor');
    $result = $this->applyFilter($this->userRole, $this->getQueryBuilder(), $segmentFilter);
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber1->getEmail())->equals('user-role-test1@example.com');
    expect($subscriber2->getEmail())->equals('user-role-test3@example.com');
  }

  public function testItAppliesFilterAny(): void {
    $segmentFilter = $this->getSegmentFilter(['editor', 'author']);
    $result = $this->applyFilter($this->userRole, $this->getQueryBuilder(), $segmentFilter);
    expect(count($result))->equals(3);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $subscriber3 = $this->entityManager->find(SubscriberEntity::class, $result[2]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);
    expect($subscriber1->getEmail())->equals('user-role-test1@example.com');
    expect($subscriber2->getEmail())->equals('user-role-test3@example.com');
    expect($subscriber3->getEmail())->equals('user-role-test4@example.com');
  }

  public function testItAppliesFilterNone(): void {
    $segmentFilter = $this->getSegmentFilter(['administrator', 'author', 'subscriber'], DynamicSegmentFilterData::OPERATOR_NONE);
    $result = $this->applyFilter($this->userRole, $this->getQueryBuilder(), $segmentFilter);
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber1->getEmail())->equals('user-role-test1@example.com');
    expect($subscriber2->getEmail())->equals('user-role-test3@example.com');
  }

  public function testItAppliesFilterAll(): void {
    $segmentFilter = $this->getSegmentFilter(['subscriber', 'merchant'], DynamicSegmentFilterData::OPERATOR_ALL);
    $result = $this->applyFilter($this->userRole, $this->getQueryBuilder(), $segmentFilter);
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('user-role-test5@example.com');
  }

  public function testItDoesntGetSubString(): void {
    $segmentFilter = $this->getSegmentFilter('edit');
    $result = $this->applyFilter($this->userRole, $this->getQueryBuilder(), $segmentFilter);
    expect(count($result))->equals(0);
  }

  private function getQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->orderBy('email')
      ->select("$subscribersTable.id")
      ->from($subscribersTable);
  }

  private function applyFilter(UserRole $userRole, QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $segmentFilter): array {
    $queryBuilder = $userRole->apply($queryBuilder, $segmentFilter);
    $statement = $queryBuilder->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    return $statement->fetchAll();
  }

  /**
   * @param string[]|string $role
   * @param string $operator
   * @return DynamicSegmentFilterEntity
   */
  private function getSegmentFilter($role, $operator = null): DynamicSegmentFilterEntity {
    $filterData = [
      'wordpressRole' => $role,
    ];
    if ($operator) {
      $filterData['operator'] = $operator;
    }
    $data = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, UserRole::TYPE, $filterData);
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $data);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $dynamicSegmentFilter;
  }

  public function _after(): void {
    parent::_after();
    $this->cleanup();
  }

  private function cleanup(): void {
    $this->cleanWpUsers();
  }

  private function cleanWpUsers(): void {
    $emails = [
      'user-role-test1@example.com',
      'user-role-test2@example.com',
      'user-role-test3@example.com',
      'user-role-test4@example.com',
      'user-role-test5@example.com',
      'user-role-test6@example.com',
    ];
    foreach ($emails as $email) {
      $this->tester->deleteWordPressUser($email);
    }
  }
}
