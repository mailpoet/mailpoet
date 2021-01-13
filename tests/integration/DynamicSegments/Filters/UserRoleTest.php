<?php

namespace MailPoet\DynamicSegments\Filters;

use MailPoet\Models\Subscriber;

class UserRoleTest extends \MailPoetTest {
  public function _before() {
    $this->cleanData();
    $this->tester->createWordPressUser('user-role-test1@example.com', 'editor');
    $this->tester->createWordPressUser('user-role-test2@example.com', 'administrator');
    $this->tester->createWordPressUser('user-role-test3@example.com', 'editor');
  }

  public function testItConstructsQuery() {
    $userRole = new UserRole('editor', 'and');
    $sql = $userRole->toSql(Subscriber::selectExpr('*')); // @phpstan-ignore-line
    expect($sql->count())->equals(2);
  }

  public function testItDoesntGetSubString() {
    $userRole = new UserRole('edit', 'and');
    $sql = $userRole->toSql(Subscriber::selectExpr('*')); // @phpstan-ignore-line
    expect($sql->count())->equals(0);
  }

  public function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    $emails = ['user-role-test1@example.com', 'user-role-test2@example.com', 'user-role-test3@example.com'];
    foreach ($emails as $email) {
      $this->tester->deleteWordPressUser($email);
    }
  }
}
