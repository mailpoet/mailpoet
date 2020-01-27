<?php

namespace MailPoet\Segments;

require_once('DynamicListingsHandlerMock.php');

use Codeception\Util\Stub;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;
use PHPUnit\Framework\MockObject\MockObject;

class SubscribersListingsTest extends \MailPoetTest {
  public $subscriber2;
  public $subscriber1;
  public $segment2;
  public $segment1;

  /** @var SubscribersListings */
  private $finder;

  public function _before() {
    parent::_before();
    $this->finder = ContainerWrapper::getInstance()->get(SubscribersListings::class);
    $this->cleanData();
    $this->segment1 = Segment::createOrUpdate(['name' => 'Segment 1', 'type' => 'default']);
    $this->segment2 = Segment::createOrUpdate(['name' => 'Segment 3', 'type' => 'not default']);
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
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
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

  public function testGetListingsUsingFilter() {
    /** @var MockObject $mock */
    $mock = Stub::makeEmpty('MailPoet\Test\Segments\DynamicListingsHandlerMock', ['get']);
    $mock
      ->expects($this->once())
      ->method('get')
      ->will($this->returnValue('dynamic listings'));

    remove_all_filters('mailpoet_get_subscribers_listings_in_segment_handlers');
    (new WPFunctions)->addFilter('mailpoet_get_subscribers_listings_in_segment_handlers', function () use ($mock) {
      return [$mock];
    });

    $listings = $this->finder->getListingsInSegment(['filter' => ['segment' => $this->segment2->id]]);
    expect($listings)->equals('dynamic listings');
  }

  public function testTryToGetListingsForSegmentWithoutHandler() {
    $this->expectException('InvalidArgumentException');
    remove_all_filters('mailpoet_get_subscribers_listings_in_segment_handlers');
    $this->finder->getListingsInSegment(['filter' => ['segment' => $this->segment2->id]]);
  }

}
