<?php

namespace MailPoet\Segments;

require_once('DynamicListingsHandlerMock.php');

use Codeception\Util\Stub;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\WP\Hooks;

class SubscribersListingsTest extends \MailPoetTest {

  function _before() {
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    $this->segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1', 'type' => 'default'));
    $this->segment_2 = Segment::createOrUpdate(array('name' => 'Segment 3', 'type' => 'not default'));
    $this->subscriber_1 = Subscriber::createOrUpdate(array(
      'email' => 'john@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => array(
        $this->segment_1->id,
      ),
    ));
    $this->subscriber_2 = Subscriber::createOrUpdate(array(
      'email' => 'jake@mailpoet.com',
      'first_name' => 'Jake',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => array(
        $this->segment_2->id,
      ),
    ));
    SubscriberSegment::resubscribeToAllSegments($this->subscriber_1);
    SubscriberSegment::resubscribeToAllSegments($this->subscriber_2);
  }

  function testTryToGetListingsWithoutPassingSegment() {
    $finder = new SubscribersListings();
    $this->setExpectedException('InvalidArgumentException');
    $finder->getListingsInSegment(array());
  }

  function testGetListingsForDefaultSegment() {
    $finder = new SubscribersListings();
    $listings = $finder->getListingsInSegment(array('filter'=> array('segment' => $this->segment_1->id)));
    expect($listings['items'])->count(1);
  }

  function testGetListingsForNonExistingSegmen() {
    $finder = new SubscribersListings();
    $listings = $finder->getListingsInSegment(array('filter'=> array('segment' => 'non-existing-id')));
    expect($listings['items'])->notEmpty();
  }

  function testGetListingsUsingFilter() {
    $mock = Stub::makeEmpty('MailPoet\Test\Segments\DynamicListingsHandlerMock', array('get'));
    $mock
      ->expects($this->once())
      ->method('get')
      ->will($this->returnValue('dynamic listings'));

    remove_all_filters('mailpoet_get_subscribers_listings_in_segment_handlers');
    Hooks::addFilter('mailpoet_get_subscribers_listings_in_segment_handlers', function () use ($mock) {
      return array($mock);
    });

    $finder = new SubscribersListings();
    $listings = $finder->getListingsInSegment(array('filter'=> array('segment' => $this->segment_2->id)));
    expect($listings)->equals('dynamic listings');
  }

  function testTryToGetListingsForSegmentWithout() {
    $finder = new SubscribersListings();
    $this->setExpectedException('InvalidArgumentException');
    remove_all_filters('mailpoet_get_subscribers_listings_in_segment_handlers');
    $finder->getListingsInSegment(array('filter'=> array('segment' => $this->segment_2->id)));
  }

}
