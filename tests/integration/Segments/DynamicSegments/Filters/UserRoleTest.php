<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;

class UserRoleTest extends \MailPoetTest {

  private $userRole;

  public function _before() {
    $this->userRole = $this->diContainer->get(UserRole::class);
    $this->cleanWpUsers();
    // Insert WP users and subscribers are created automatically
    $this->tester->createWordPressUser('user-role-test1@example.com', 'editor');
    $this->tester->createWordPressUser('user-role-test2@example.com', 'administrator');
    $this->tester->createWordPressUser('user-role-test3@example.com', 'editor');
  }

  public function testItAppliesFilter() {
    $segmentFilter = $this->getSegmentFilter('editor');
    $queryBuilder = $this->userRole->apply($this->getQueryBuilder(), $segmentFilter);
    $result = $queryBuilder->execute()->fetchAll();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    assert($subscriber2 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('user-role-test1@example.com');
    expect($subscriber2->getEmail())->equals('user-role-test3@example.com');
  }

  public function testItDoesntGetSubString() {
    $segmentFilter = $this->getSegmentFilter('edit');
    $queryBuilder = $this->userRole->apply($this->getQueryBuilder(), $segmentFilter);
    $result = $queryBuilder->execute()->fetchAll();
    expect(count($result))->equals(0);
  }

  private function getQueryBuilder() {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id")
      ->from($subscribersTable);
  }

  private function getSegmentFilter(string $role): DynamicSegmentFilterEntity {
    return new DynamicSegmentFilterEntity(
      new SegmentEntity('segment', SegmentEntity::TYPE_DYNAMIC, 'Description'),
      [
        'segmentType' => DynamicSegmentFilterEntity::TYPE_USER_ROLE,
        'wordpressRole' => $role,
      ]
    );
  }

  public function _after() {
    $this->cleanWpUsers();
    $this->truncateEntity(SubscriberEntity::class);
  }

  private function cleanWpUsers() {
    $emails = ['user-role-test1@example.com', 'user-role-test2@example.com', 'user-role-test3@example.com'];
    foreach ($emails as $email) {
      $this->tester->deleteWordPressUser($email);
    }
  }
}
