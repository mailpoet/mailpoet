<?php

namespace MailPoet\Models;

use MailPoet\DynamicSegments\Filters\UserRole;
use MailPoetVendor\Idiorm\ORM;

class SubscribersInDynamicSegmentTest extends \MailPoetTest {
  public $dynamicSegment;

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
    $this->tester->createWordPressUser('user-role-test1@example.com', 'editor');
    $this->tester->createWordPressUser('user-role-test2@example.com', 'administrator');
    $this->tester->createWordPressUser('user-role-test3@example.com', 'editor');
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
      $this->tester->deleteWordPressUser($email);
    }
  }
}
