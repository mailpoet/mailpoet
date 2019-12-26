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

class SubscribersListingsTest extends \MailPoetTest {

  /** @var SubscribersListings */
  private $finder;

  public function _before() {
    parent::_before();
    $this->finder = ContainerWrapper::getInstance()->get(SubscribersListings::class);
    $this->cleanData();
    $this->segment_1 = Segment::createOrUpdate(['name' => 'Segment 1', 'type' => 'default']);
    $this->segment_2 = Segment::createOrUpdate(['name' => 'Segment 3', 'type' => 'not default']);
    $this->subscriber_1 = Subscriber::createOrUpdate([
      'email' => 'john@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $this->segment_1->id,
      ],
    ]);
    $this->subscriber_2 = Subscriber::createOrUpdate([
      'email' => 'jake@mailpoet.com',
      'first_name' => 'Jake',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $this->segment_2->id,
      ],
    ]);
    SubscriberSegment::resubscribeToAllSegments($this->subscriber_1);
    SubscriberSegment::resubscribeToAllSegments($this->subscriber_2);
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
    $this->setExpectedException('InvalidArgumentException');
    $this->finder->getListingsInSegment([]);
  }

  public function testGetListingsForDefaultSegment() {
    $listings = $this->finder->getListingsInSegment(['filter' => ['segment' => $this->segment_1->id]]);
    expect($listings['items'])->count(1);
  }

  public function testGetListingsForNonExistingSegmen() {
    $listings = $this->finder->getListingsInSegment(['filter' => ['segment' => 'non-existing-id']]);
    expect($listings['items'])->notEmpty();
  }

  public function testGetListingsUsingFilter() {
    $mock = Stub::makeEmpty('MailPoet\Test\Segments\DynamicListingsHandlerMock', ['get']);
    $mock
      ->expects($this->once())
      ->method('get')
      ->will($this->returnValue('dynamic listings'));

    remove_all_filters('mailpoet_get_subscribers_listings_in_segment_handlers');
    (new WPFunctions)->addFilter('mailpoet_get_subscribers_listings_in_segment_handlers', function () use ($mock) {
      return [$mock];
    });

    $listings = $this->finder->getListingsInSegment(['filter' => ['segment' => $this->segment_2->id]]);
    expect($listings)->equals('dynamic listings');
  }

  public function testTryToGetListingsForSegmentWithoutHandler() {
    $this->setExpectedException('InvalidArgumentException');
    remove_all_filters('mailpoet_get_subscribers_listings_in_segment_handlers');
    $this->finder->getListingsInSegment(['filter' => ['segment' => $this->segment_2->id]]);
  }

}
