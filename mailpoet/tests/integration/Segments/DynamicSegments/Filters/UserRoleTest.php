<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SubscriberEntity;

class UserRoleTest extends \MailPoetTest {

  /** @var UserRole */
  private $userRoleFilter;

  public function _before(): void {
    global $wpdb;
    $this->userRoleFilter = $this->diContainer->get(UserRole::class);
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
    $segmentFilterData = $this->getSegmentFilterData('editor');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->userRoleFilter);
    $this->assertEqualsCanonicalizing(['user-role-test1@example.com', 'user-role-test3@example.com'], $emails);
  }

  public function testItAppliesFilterAny(): void {
    $segmentFilterData = $this->getSegmentFilterData(['editor', 'author']);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->userRoleFilter);
    $this->assertEqualsCanonicalizing(['user-role-test1@example.com', 'user-role-test3@example.com', 'user-role-test4@example.com'], $emails);
  }

  public function testItAppliesFilterNone(): void {
    $segmentFilterData = $this->getSegmentFilterData(['administrator', 'author', 'subscriber'], DynamicSegmentFilterData::OPERATOR_NONE);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->userRoleFilter);
    $this->assertEqualsCanonicalizing(['user-role-test1@example.com', 'user-role-test3@example.com'], $emails);
  }

  public function testItAppliesFilterAll(): void {
    $segmentFilterData = $this->getSegmentFilterData(['subscriber', 'merchant'], DynamicSegmentFilterData::OPERATOR_ALL);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->userRoleFilter);
    $this->assertEqualsCanonicalizing(['user-role-test5@example.com'], $emails);
  }

  public function testItDoesntGetSubString(): void {
    $segmentFilterData = $this->getSegmentFilterData('edit');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->userRoleFilter);
    expect($emails)->count(0);
  }

  /**
   * @param string[]|string $role
   * @param string|null $operator
   * @return DynamicSegmentFilterData
   */
  private function getSegmentFilterData($role, string $operator = null): DynamicSegmentFilterData {
    $filterData = [
      'wordpressRole' => $role,
    ];
    if ($operator) {
      $filterData['operator'] = $operator;
    }
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, UserRole::TYPE, $filterData);
  }

  public function _after(): void {
    parent::_after();
    $this->cleanup();
  }

  private function cleanup(): void {
    $this->cleanWpUsers();
    $this->truncateEntity(SubscriberEntity::class);
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
