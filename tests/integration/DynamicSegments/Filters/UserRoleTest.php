<?php

namespace MailPoet\DynamicSegments\Filters;

use MailPoet\Models\Subscriber;

class UserRoleTest extends \MailPoetTest {

  public function _before() {
    $this->cleanData();
    wp_insert_user([
      'user_login' => 'user-role-test1',
      'user_email' => 'user-role-test1@example.com',
      'role' => 'editor',
      'user_pass' => '12123154',
    ]);
    wp_insert_user([
      'user_login' => 'user-role-test2',
      'user_email' => 'user-role-test2@example.com',
      'role' => 'administrator',
      'user_pass' => '12123154',
    ]);
    wp_insert_user([
      'user_login' => 'user-role-test3',
      'user_email' => 'user-role-test3@example.com',
      'role' => 'editor',
      'user_pass' => '12123154',
    ]);
  }

  public function testItConstructsQuery() {
    $userRole = new UserRole('editor', 'and');
    $sql = $userRole->toSql(Subscriber::selectExpr('*'));
    expect($sql->count())->equals(2);
  }

  public function testItDoesntGetSubString() {
    $userRole = new UserRole('edit', 'and');
    $sql = $userRole->toSql(Subscriber::selectExpr('*'));
    expect($sql->count())->equals(0);
  }

  public function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    $emails = ['user-role-test1@example.com', 'user-role-test2@example.com', 'user-role-test3@example.com'];
    foreach ($emails as $email) {
      $user = get_user_by('email', $email);
      if ($user) {
        wp_delete_user($user->ID);
      }
    }
  }
}
