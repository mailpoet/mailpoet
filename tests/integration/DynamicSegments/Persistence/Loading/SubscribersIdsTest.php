<?php

namespace MailPoet\Premium\DynamicSegments\Persistence\Loading;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use MailPoet\Models\Subscriber;
use MailPoet\Premium\DynamicSegments\Filters\UserRole;
use MailPoet\Premium\Models\DynamicSegment;

class SubscribersIdsTest extends \MailPoetTest {

  private $editors_wp_ids = [];

  function _before() {
    $this->cleanData();
    $this->editors_wp_ids[] = wp_insert_user([
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
    $this->editors_wp_ids[] = wp_insert_user([
      'user_login' => 'user-role-test3',
      'user_email' => 'user-role-test3@example.com',
      'role' => 'editor',
      'user_pass' => '12123154',
    ]);
  }

  function testItConstructsSubscribersIdQueryForAnyDynamicSegment() {
    $userRole = DynamicSegment::create();
    $userRole->hydrate([
      'name' => 'segment',
      'description' => 'description',
    ]);
    $userRole->setFilters([new UserRole('editor', 'and')]);
    $loader = new SubscribersIds();
    $result = $loader->load($userRole);
    $wp_ids = [
      Subscriber::findOne($result[0]->id)->wp_user_id,
      Subscriber::findOne($result[1]->id)->wp_user_id,
    ];
    $this->assertEquals($wp_ids, $this->editors_wp_ids, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = true);
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
