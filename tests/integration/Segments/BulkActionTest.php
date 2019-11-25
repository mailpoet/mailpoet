<?php

namespace MailPoet\Segments;

use Codeception\Util\Stub;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;

require_once('SubscribersBulkActionHandlerMock.php');

class BulkActionTest extends \MailPoetTest {

  function _before() {
    parent::_before();
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

  function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }

  function testBulkActionWithoutSegment() {
    $handler = new BulkAction([]);
    $this->setExpectedException('InvalidArgumentException');
    $handler->apply();
  }

  function testBulkActionForDefaultSegment() {
    $handler = new BulkAction([
      'listing' => ['filter' => ['segment' => $this->segment_1->id]],
      'action' => 'trash',
    ]);
    $result = $handler->apply();
    expect($result['count'])->equals(1);
  }

  function testBulkActionForUnknownSegment() {
    $handler = new BulkAction([
      'listing' => ['filter' => ['segment' => 'this-segment-doesnt-exist']],
      'action' => 'trash',
    ]);
    $result = $handler->apply();
    expect($result)->notEmpty();
  }

  function testForUnknownSegmentTypeWithoutHandler() {
    $handler = new BulkAction([
      'listing' => ['filter' => ['segment' => $this->segment_2->id]],
      'action' => 'trash',
    ]);
    $this->setExpectedException('InvalidArgumentException');
    remove_all_filters('mailpoet_subscribers_in_segment_apply_bulk_action_handlers');
    $handler->apply();
  }

  function testBulkActionUsingFilter() {
    $mock = Stub::makeEmpty('\MailPoet\Test\Segments\SubscribersBulkActionHandlerMock', ['apply']);
    $mock
      ->expects($this->once())
      ->method('apply')
      ->will($this->returnValue('result'));

    remove_all_filters('mailpoet_subscribers_in_segment_apply_bulk_action_handlers');
    (new WPFunctions)->addFilter('mailpoet_subscribers_in_segment_apply_bulk_action_handlers', function () use ($mock) {
      return [$mock];
    });

    $handler = new BulkAction([
      'listing' => ['filter' => ['segment' => $this->segment_2->id]],
      'action' => 'trash',
    ]);
    $result = $handler->apply();
    expect($result)->equals('result');
  }

}
