<?php

namespace MailPoet\Segments;

require_once('FinderMock.php');

use Codeception\Util\Stub;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Hooks;

class SubscribersFinderTest extends \MailPoetTest {

  function _before() {
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    $this->segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1', 'type' => 'default'));
    $this->segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2', 'type' => 'default'));
    $this->segment_3 = Segment::createOrUpdate(array('name' => 'Segment 3', 'type' => 'not default'));
    $this->subscriber_1 = Subscriber::createOrUpdate(array(
      'email' => 'john@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ));
    $this->subscriber_2 = Subscriber::createOrUpdate(array(
      'email' => 'jane@mailpoet.com',
      'first_name' => 'Jane',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => array(
        $this->segment_1->id,
      ),
    ));
    $this->subscriber_3 = Subscriber::createOrUpdate(array(
      'email' => 'jake@mailpoet.com',
      'first_name' => 'Jake',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => array(
        $this->segment_3->id,
      ),
    ));
    SubscriberSegment::resubscribeToAllSegments($this->subscriber_2);
    SubscriberSegment::resubscribeToAllSegments($this->subscriber_3);
    $this->sending = SendingTask::create();
  }

  function testGetSubscribersInSegmentDefaultSegment() {
    $finder = new SubscribersFinder();
    $subscribers = $finder->getSubscribersByList(array(
      array('id' => $this->segment_1->id, 'type' => Segment::TYPE_DEFAULT),
      array('id' => $this->segment_2->id, 'type' => Segment::TYPE_DEFAULT),
    ));
    expect($subscribers)->count(1);
    expect($subscribers[$this->subscriber_2->id]['id'])->equals($this->subscriber_2->id);
  }

  function testGetSubscribersNoSegment() {
    $finder = new SubscribersFinder();
    $subscribers = $finder->getSubscribersByList(array(
      array('id' => $this->segment_1->id, 'type' => 'UNKNOWN SEGMENT'),
    ));
    expect($subscribers)->count(0);
  }

  function testGetSubscribersUsingFinder() {
    $mock = Stub::makeEmpty('MailPoet\Segments\FinderMock', array('getSubscriberIdsInSegment'));
    $mock
      ->expects($this->once())
      ->method('getSubscriberIdsInSegment')
      ->will($this->returnValue(array($this->subscriber_1->id)));

    remove_all_filters('mailpoet_get_subscribers_in_segment_finders');
    Hooks::addFilter('mailpoet_get_subscribers_in_segment_finders', function () use ($mock) {
      return array($mock);
    });

    $finder = new SubscribersFinder();
    $subscribers = $finder->getSubscribersByList(array(
      array('id' => $this->segment_2->id, 'type' => ''),
    ));
    expect($subscribers)->count(1);
  }

  function testGetSubscribersUsingFinderMakesResultUnique() {
    $mock = Stub::makeEmpty('MailPoet\Segments\FinderMock', array('getSubscriberIdsInSegment'));
    $mock
      ->expects($this->exactly(2))
      ->method('getSubscriberIdsInSegment')
      ->will($this->returnValue(array($this->subscriber_1->id)));

    remove_all_filters('mailpoet_get_subscribers_in_segment_finders');
    Hooks::addFilter('mailpoet_get_subscribers_in_segment_finders', function () use ($mock) {
      return array($mock);
    });

    $finder = new SubscribersFinder();
    $subscribers = $finder->getSubscribersByList(array(
      array('id' => $this->segment_2->id, 'type' => ''),
      array('id' => $this->segment_2->id, 'type' => ''),
    ));
    expect($subscribers)->count(1);
  }

  function testFindSubscribersInSegmentInSegmentDefaultSegment() {
    $finder = new SubscribersFinder();
    $subscribers = $finder->findSubscribersInSegments(array($this->subscriber_2->id), array($this->segment_1->id));
    expect($subscribers)->count(1);
    expect($subscribers[$this->subscriber_2->id])->equals($this->subscriber_2->id);
  }

  function testFindSubscribersInSegmentUsingFinder() {
    $mock = Stub::makeEmpty('MailPoet\Segments\FinderMock', array('findSubscribersInSegment'));
    $mock
      ->expects($this->once())
      ->method('findSubscribersInSegment')
      ->will($this->returnValue(array($this->subscriber_3)));

    remove_all_filters('mailpoet_get_subscribers_in_segment_finders');
    Hooks::addFilter('mailpoet_get_subscribers_in_segment_finders', function () use ($mock) {
      return array($mock);
    });

    $finder = new SubscribersFinder();
    $subscribers = $finder->findSubscribersInSegments(array($this->subscriber_3->id), array($this->segment_3->id));
    expect($subscribers)->count(1);
    expect($subscribers)->contains($this->subscriber_3->id);
  }

  function testFindSubscribersInSegmentUsingFinderMakesResultUnique() {
    $mock = Stub::makeEmpty('MailPoet\Segments\FinderMock', array('findSubscribersInSegment'));
    $mock
      ->expects($this->exactly(2))
      ->method('findSubscribersInSegment')
      ->will($this->returnValue(array($this->subscriber_3)));

    remove_all_filters('mailpoet_get_subscribers_in_segment_finders');
    Hooks::addFilter('mailpoet_get_subscribers_in_segment_finders', function () use ($mock) {
      return array($mock);
    });

    $finder = new SubscribersFinder();
    $subscribers = $finder->findSubscribersInSegments(array($this->subscriber_3->id), array($this->segment_3->id, $this->segment_3->id));
    expect($subscribers)->count(1);
  }

  function testItAddsSubscribersToTaskFromStaticSegments() {
    $finder = new SubscribersFinder();
    $subscribers_count = $finder->addSubscribersToTaskFromSegments(
      $this->sending->task(),
      array(
        array('id' => $this->segment_1->id, 'type' => Segment::TYPE_DEFAULT),
        array('id' => $this->segment_2->id, 'type' => Segment::TYPE_DEFAULT),
      )
    );
    expect($subscribers_count)->equals(1);
    expect($this->sending->getSubscribers())->equals(array($this->subscriber_2->id));
  }

  function testItDoesNotAddSubscribersToTaskFromNoSegment() {
    $finder = new SubscribersFinder();
    $subscribers_count = $finder->addSubscribersToTaskFromSegments(
      $this->sending->task(),
      array(
        array('id' => $this->segment_1->id, 'type' => 'UNKNOWN SEGMENT'),
      )
    );
    expect($subscribers_count)->equals(0);
  }

  function testItAddsSubscribersToTaskFromDynamicSegments() {
    $mock = Stub::makeEmpty('MailPoet\Segments\FinderMock', array('getSubscriberIdsInSegment'));
    $mock
      ->expects($this->once())
      ->method('getSubscriberIdsInSegment')
      ->will($this->returnValue(array(array('id' => $this->subscriber_1->id))));

    remove_all_filters('mailpoet_get_subscribers_in_segment_finders');
    Hooks::addFilter('mailpoet_get_subscribers_in_segment_finders', function () use ($mock) {
      return array($mock);
    });

    $finder = new SubscribersFinder();
    $subscribers_count = $finder->addSubscribersToTaskFromSegments(
      $this->sending->task(),
      array(
        array('id' => $this->segment_2->id, 'type' => ''),
      )
    );
    expect($subscribers_count)->equals(1);
    expect($this->sending->getSubscribers())->equals(array($this->subscriber_1->id));
  }

  function testItAddsSubscribersToTaskFromStaticAndDynamicSegments() {
    $finder = new SubscribersFinder();

    $mock = Stub::makeEmpty('MailPoet\Segments\FinderMock', array('getSubscriberIdsInSegment'));
    $mock
      ->expects($this->once())
      ->method('getSubscriberIdsInSegment')
      ->will($this->returnValue(array(array('id' => $this->subscriber_2->id))));
    remove_all_filters('mailpoet_get_subscribers_in_segment_finders');
    Hooks::addFilter('mailpoet_get_subscribers_in_segment_finders', function () use ($mock) {
      return array($mock);
    });

    $subscribers_count = $finder->addSubscribersToTaskFromSegments(
      $this->sending->task(),
      array(
        array('id' => $this->segment_1->id, 'type' => Segment::TYPE_DEFAULT),
        array('id' => $this->segment_2->id, 'type' => Segment::TYPE_DEFAULT),
        array('id' => $this->segment_3->id, 'type' => ''),
      )
    );

    expect($subscribers_count)->equals(1);
    expect($this->sending->getSubscribers())->equals(array($this->subscriber_2->id));
  }

}
