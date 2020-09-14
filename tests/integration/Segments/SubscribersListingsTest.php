<?php

namespace MailPoet\Segments;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\DynamicSegmentFilter;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoetVendor\Idiorm\ORM;

class SubscribersListingsTest extends \MailPoetTest {
  public $subscriber2;
  public $subscriber1;
  public $segment2;
  public $segment1;
  public $dynamicSegment;

  /** @var SubscribersListings */
  private $finder;

  public function _before() {
    parent::_before();
    $this->finder = ContainerWrapper::getInstance()->get(SubscribersListings::class);
    $this->cleanData();
    wp_insert_user([
      'user_login' => 'user-role-test1',
      'user_email' => 'user-role-test1@example.com',
      'role' => 'editor',
      'user_pass' => '12123154',
    ]);
    $this->segment1 = Segment::createOrUpdate(['name' => 'Segment 1', 'type' => 'default']);
    $this->segment2 = Segment::createOrUpdate(['name' => 'Segment 3', 'type' => 'not default']);
    $dynamicSegmentFactory = new DynamicSegment();
    $this->dynamicSegment = $dynamicSegmentFactory
      ->withName('Dynamic')
      ->withUserRoleFilter('editor')
      ->create();
    $this->subscriber1 = Subscriber::createOrUpdate([
      'email' => 'john@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $this->segment1->id,
      ],
    ]);
    $this->subscriber2 = Subscriber::createOrUpdate([
      'email' => 'jake@mailpoet.com',
      'first_name' => 'Jake',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $this->segment2->id,
      ],
    ]);
    SubscriberSegment::resubscribeToAllSegments($this->subscriber1);
    SubscriberSegment::resubscribeToAllSegments($this->subscriber2);
  }

  public function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . DynamicSegmentFilter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    $user = get_user_by('email', 'user-role-test1@example.com');
    if ($user) {
      \wp_delete_user($user->ID);
    }
  }

  public function testTryToGetListingsWithoutPassingSegment() {
    $this->expectException('InvalidArgumentException');
    $this->finder->getListingsInSegment([]);
  }

  public function testGetListingsForDefaultSegment() {
    $listings = $this->finder->getListingsInSegment(['filter' => ['segment' => $this->segment1->id]]);
    expect($listings['items'])->count(1);
  }

  public function testGetListingsForNonExistingSegmen() {
    $listings = $this->finder->getListingsInSegment(['filter' => ['segment' => 'non-existing-id']]);
    expect($listings['items'])->notEmpty();
  }

  public function testGetListingsForDynamicSegment() {
    $listings = $this->finder->getListingsInSegment(['filter' => ['segment' => $this->dynamicSegment->id]]);
    expect($listings['items'])->count(1);
    expect($listings['items'][0]->email)->equals('user-role-test1@example.com');
  }

  public function testTryToGetListingsForSegmentWithoutHandler() {
    $this->expectException('InvalidArgumentException');
    $this->finder->getListingsInSegment(['filter' => ['segment' => $this->segment2->id]]);
  }
}
