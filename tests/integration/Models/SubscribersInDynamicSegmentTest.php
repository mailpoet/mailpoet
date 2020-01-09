<?php

namespace MailPoet\Models;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use MailPoet\DynamicSegments\Filters\UserRole;
use MailPoetVendor\Idiorm\ORM;

class SubscribersInDynamicSegmentTest extends \MailPoetTest {
  public $dynamic_segment;

  public function _before() {
    $this->cleanData();
    $this->dynamicSegment = DynamicSegment::createOrUpdate([
      'name' => 'name',
      'description' => 'desc',
    ]);
    $filter = new UserRole('editor', 'and');
    $dataFilter = DynamicSegmentFilter::create();
    $dataFilter->segmentId = $this->dynamicSegment->id;
    $dataFilter->filterData = $filter->toArray();
    $dataFilter->save();
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

  public function testListingQuery() {
    $listingData = [
      'filter' => ['segment' => $this->dynamicSegment->id],
      'group' => 'all',
      'search' => '',
    ];
    $query = SubscribersInDynamicSegment::listingQuery($listingData);
    $data = $query->orderByAsc('email')->findMany();
    expect($data)->count(2);
    expect($data[0]->email)->equals('user-role-test1@example.com');
    expect($data[1]->email)->equals('user-role-test3@example.com');
  }

  public function testListingQueryWithSearch() {
    $listingData = [
      'filter' => ['segment' => $this->dynamicSegment->id],
      'group' => 'all',
      'search' => 'user-role-test1',
    ];
    $query = SubscribersInDynamicSegment::listingQuery($listingData);
    $data = $query->findMany();
    expect($data)->count(1);
    expect($data[0]->email)->equals('user-role-test1@example.com');
  }

  public function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . DynamicSegmentFilter::$_table);
    $emails = ['user-role-test1@example.com', 'user-role-test2@example.com', 'user-role-test3@example.com'];
    foreach ($emails as $email) {
      $user = get_user_by('email', $email);
      if (!$user) {
        continue;
      }

      if (is_multisite()) {
        wpmu_delete_user($user->ID);
      } else {
        wp_delete_user($user->ID);
      }
    }
  }

}
