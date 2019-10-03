<?php

namespace MailPoet\Premium\DynamicSegments\Persistence\Loading;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use MailPoet\Premium\DynamicSegments\Filters\UserRole;
use MailPoet\Premium\Models\DynamicSegment;

class SubscribersCountTest extends \MailPoetTest {
  function _before() {
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

  function testItConstructsQuery() {
    $userRole = DynamicSegment::create();
    $userRole->hydrate([
      'name' => 'segment',
      'description' => 'description',
    ]);
    $userRole->setFilters([new UserRole('editor', 'and')]);

    $loader = new SubscribersCount();
    $count = $loader->getSubscribersCount($userRole);
    expect($count)->equals(2);
  }

  function _after() {
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
