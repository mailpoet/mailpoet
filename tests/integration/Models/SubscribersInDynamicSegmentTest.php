<?php

namespace MailPoet\Models;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use MailPoet\DynamicSegments\Filters\UserRole;

class SubscribersInDynamicSegmentTest extends \MailPoetTest {

  function _before() {
    $this->cleanData();
    $this->dynamic_segment = DynamicSegment::createOrUpdate([
      'name' => 'name',
      'description' => 'desc',
    ]);
    $filter = new UserRole('editor', 'and');
    $data_filter = DynamicSegmentFilter::create();
    $data_filter->segment_id = $this->dynamic_segment->id;
    $data_filter->filter_data = $filter->toArray();
    $data_filter->save();
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

  function testListingQuery() {
    $listing_data = [
      'filter' => ['segment' => $this->dynamic_segment->id],
      'group' => 'all',
      'search' => '',
    ];
    $query = SubscribersInDynamicSegment::listingQuery($listing_data);
    $data = $query->orderByAsc('email')->findMany();
    expect($data)->count(2);
    expect($data[0]->email)->equals('user-role-test1@example.com');
    expect($data[1]->email)->equals('user-role-test3@example.com');
  }

  function testListingQueryWithSearch() {
    $listing_data = [
      'filter' => ['segment' => $this->dynamic_segment->id],
      'group' => 'all',
      'search' => 'user-role-test1',
    ];
    $query = SubscribersInDynamicSegment::listingQuery($listing_data);
    $data = $query->findMany();
    expect($data)->count(1);
    expect($data[0]->email)->equals('user-role-test1@example.com');
  }

  function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    \ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . DynamicSegmentFilter::$_table);
    $emails = ['user-role-test1@example.com', 'user-role-test2@example.com', 'user-role-test3@example.com'];
    foreach ($emails as $email) {
      $user = get_user_by('email', $email);
      if ($user) {
        wp_delete_user($user->ID);
      }
    }
  }

}
