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
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;
use PHPUnit\Framework\MockObject\MockObject;

class SubscribersFinderTest extends \MailPoetTest {
  public $sending;
  public $subscriber3;
  public $subscriber2;
  public $subscriber1;
  public $segment3;
  public $segment2;
  public $segment1;

  public function _before() {
    parent::_before();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    $this->segment1 = Segment::createOrUpdate(['name' => 'Segment 1', 'type' => 'default']);
    $this->segment2 = Segment::createOrUpdate(['name' => 'Segment 2', 'type' => 'default']);
    $this->segment3 = Segment::createOrUpdate(['name' => 'Segment 3', 'type' => 'not default']);
    $this->subscriber1 = Subscriber::createOrUpdate([
      'email' => 'john@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    $this->subscriber2 = Subscriber::createOrUpdate([
      'email' => 'jane@mailpoet.com',
      'first_name' => 'Jane',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $this->segment1->id,
      ],
    ]);
    $this->subscriber3 = Subscriber::createOrUpdate([
      'email' => 'jake@mailpoet.com',
      'first_name' => 'Jake',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $this->segment3->id,
      ],
    ]);
    SubscriberSegment::resubscribeToAllSegments($this->subscriber2);
    SubscriberSegment::resubscribeToAllSegments($this->subscriber3);
    $this->sending = SendingTask::create();
  }

  public function testFindSubscribersInSegmentInSegmentDefaultSegment() {
    $finder = new SubscribersFinder();
    $deletedSegmentId = 1000; // non-existent segment
    $subscribers = $finder->findSubscribersInSegments([$this->subscriber2->id], [$this->segment1->id, $deletedSegmentId]);
    expect($subscribers)->count(1);
    expect($subscribers[$this->subscriber2->id])->equals($this->subscriber2->id);
  }

  public function testFindSubscribersInSegmentUsingFinder() {
    /** @var MockObject $mock */
    $mock = Stub::makeEmpty('MailPoet\Segments\FinderMock', ['findSubscribersInSegment']);
    $mock
      ->expects($this->once())
      ->method('findSubscribersInSegment')
      ->will($this->returnValue([$this->subscriber3]));

    remove_all_filters('mailpoet_get_subscribers_in_segment_finders');
    (new WPFunctions)->addFilter('mailpoet_get_subscribers_in_segment_finders', function () use ($mock) {
      return [$mock];
    });

    $finder = new SubscribersFinder();
    $subscribers = $finder->findSubscribersInSegments([$this->subscriber3->id], [$this->segment3->id]);
    expect($subscribers)->count(1);
    expect($subscribers)->contains($this->subscriber3->id);
  }

  public function testFindSubscribersInSegmentUsingFinderMakesResultUnique() {
    /** @var MockObject $mock */
    $mock = Stub::makeEmpty('MailPoet\Segments\FinderMock', ['findSubscribersInSegment']);
    $mock
      ->expects($this->exactly(2))
      ->method('findSubscribersInSegment')
      ->will($this->returnValue([$this->subscriber3]));

    remove_all_filters('mailpoet_get_subscribers_in_segment_finders');
    (new WPFunctions)->addFilter('mailpoet_get_subscribers_in_segment_finders', function () use ($mock) {
      return [$mock];
    });

    $finder = new SubscribersFinder();
    $subscribers = $finder->findSubscribersInSegments([$this->subscriber3->id], [$this->segment3->id, $this->segment3->id]);
    expect($subscribers)->count(1);
  }

  public function testItAddsSubscribersToTaskFromStaticSegments() {
    $finder = new SubscribersFinder();
    $subscribersCount = $finder->addSubscribersToTaskFromSegments(
      $this->sending->task(),
      [
        $this->getDummySegment($this->segment1->id, Segment::TYPE_DEFAULT),
        $this->getDummySegment($this->segment2->id, Segment::TYPE_DEFAULT),
      ]
    );
    expect($subscribersCount)->equals(1);
    expect($this->sending->getSubscribers())->equals([$this->subscriber2->id]);
  }

  public function testItDoesNotAddSubscribersToTaskFromNoSegment() {
    $finder = new SubscribersFinder();
    $subscribersCount = $finder->addSubscribersToTaskFromSegments(
      $this->sending->task(),
      [
        $this->getDummySegment($this->segment1->id, 'UNKNOWN SEGMENT'),
      ]
    );
    expect($subscribersCount)->equals(0);
  }

  public function testItAddsSubscribersToTaskFromDynamicSegments() {
    /** @var MockObject $mock */
    $mock = Stub::makeEmpty('MailPoet\Segments\FinderMock', ['getSubscriberIdsInSegment']);
    $mock
      ->expects($this->once())
      ->method('getSubscriberIdsInSegment')
      ->will($this->returnValue([['id' => $this->subscriber1->id]]));

    remove_all_filters('mailpoet_get_subscribers_in_segment_finders');
    (new WPFunctions)->addFilter('mailpoet_get_subscribers_in_segment_finders', function () use ($mock) {
      return [$mock];
    });

    $finder = new SubscribersFinder();
    $subscribersCount = $finder->addSubscribersToTaskFromSegments(
      $this->sending->task(),
      [
        $this->getDummySegment($this->segment2->id, ''),
      ]
    );
    expect($subscribersCount)->equals(1);
    expect($this->sending->getSubscribers())->equals([$this->subscriber1->id]);
  }

  public function testItAddsSubscribersToTaskFromStaticAndDynamicSegments() {
    $finder = new SubscribersFinder();

    /** @var MockObject $mock */
    $mock = Stub::makeEmpty('MailPoet\Segments\FinderMock', ['getSubscriberIdsInSegment']);
    $mock
      ->expects($this->once())
      ->method('getSubscriberIdsInSegment')
      ->will($this->returnValue([['id' => $this->subscriber2->id]]));
    remove_all_filters('mailpoet_get_subscribers_in_segment_finders');
    (new WPFunctions)->addFilter('mailpoet_get_subscribers_in_segment_finders', function () use ($mock) {
      return [$mock];
    });

    $subscribersCount = $finder->addSubscribersToTaskFromSegments(
      $this->sending->task(),
      [
        $this->getDummySegment($this->segment1->id, Segment::TYPE_DEFAULT),
        $this->getDummySegment($this->segment2->id, Segment::TYPE_DEFAULT),
        $this->getDummySegment($this->segment3->id, ''),
      ]
    );

    expect($subscribersCount)->equals(1);
    expect($this->sending->getSubscribers())->equals([$this->subscriber2->id]);
  }

  private function getDummySegment($id, $type) {
    $segment = Segment::create();
    $segment->id = $id;
    $segment->type = $type;
    return $segment;
  }

}
