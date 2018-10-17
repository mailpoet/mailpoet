<?php

namespace MailPoet\Segments;

use Codeception\Util\Stub;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\WP\Hooks;

require_once('SubscribersBulkActionHandlerMock.php');

class BulkActionTest extends \MailPoetTest {

  function _before() {
    $this->cleanData();
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

  function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }

  function testBulkActionWithoutSegment() {
    $handler = new BulkAction(array());
    $this->setExpectedException('InvalidArgumentException');
    $handler->apply();
  }

  function testBulkActionForDefaultSegment() {
    $handler = new BulkAction(array(
      'listing' => array('filter'=> array('segment' => $this->segment_1->id)),
      'action' => 'trash',
    ));
    $result = $handler->apply();
    expect($result['count'])->equals(1);
  }

  function testBulkActionForUnknownSegment() {
    $handler = new BulkAction(array(
      'listing' => array('filter'=> array('segment' => 'this-segment-doesnt-exist')),
      'action' => 'trash',
    ));
    $result = $handler->apply();
    expect($result)->notEmpty();
  }

  function testForUnknownSegmentTypeWithoutHandler() {
    $handler = new BulkAction(array(
      'listing' => array('filter'=> array('segment' => $this->segment_2->id)),
      'action' => 'trash',
    ));
    $this->setExpectedException('InvalidArgumentException');
    remove_all_filters('mailpoet_subscribers_in_segment_apply_bulk_action_handlers');
    $handler->apply();
  }

  function testBulkActionUsingFilter() {
    $mock = Stub::makeEmpty('\MailPoet\Test\Segments\SubscribersBulkActionHandlerMock', array('apply'));
    $mock
      ->expects($this->once())
      ->method('apply')
      ->will($this->returnValue('result'));

    remove_all_filters('mailpoet_subscribers_in_segment_apply_bulk_action_handlers');
    Hooks::addFilter('mailpoet_subscribers_in_segment_apply_bulk_action_handlers', function () use ($mock) {
      return array($mock);
    });

    $handler = new BulkAction(array(
      'listing' => array('filter'=> array('segment' => $this->segment_2->id)),
      'action' => 'trash',
    ));
    $result = $handler->apply();
    expect($result)->equals('result');
  }

}
