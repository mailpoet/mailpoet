<?php

namespace MailPoet\DynamicSegments\FreePluginConnectors;

use Codeception\Util\Stub;
use MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader;
use MailPoet\DynamicSegments\Persistence\Loading\SubscribersIds;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;

class SendingNewslettersSubscribersFinderTest extends \MailPoetTest {

  /** @var SingleSegmentLoader */
  private $single_segment_loader;

  /** @var SubscribersIds */
  private $subscribers_ids_loader;

  /** @var SendingNewslettersSubscribersFinder */
  private $subscribers_in_segments_finder;

  public function _before() {
    $this->single_segment_loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader');
    $this->subscribers_ids_loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SubscribersIds');
    $this->subscribers_in_segments_finder = new SendingNewslettersSubscribersFinder($this->single_segment_loader, $this->subscribers_ids_loader);
  }

  public function testFindSubscribersInSegmentReturnsEmptyIfNotDynamic() {
    $this->single_segment_loader
      ->expects($this->never())
      ->method('load');
    $this->subscribers_ids_loader
      ->expects($this->never())
      ->method('load');
    $segment = Segment::create();
    $segment->type = Segment::TYPE_DEFAULT;
    $segment->id = 3;
    $result = $this->subscribers_in_segments_finder->findSubscribersInSegment($segment, []);
    expect($result)->count(0);
  }

  public function testFindSubscribersInSegmentReturnsSubscribers() {
    $dynamic_segment = DynamicSegment::create();
    $dynamic_segment->hydrate([
      'name' => 'segment 1',
      'description' => '',
    ]);
    $ids = [1, 2, 3];
    $this->single_segment_loader
      ->expects($this->once())
      ->method('load')
      ->with($this->equalTo(3))
      ->will($this->returnValue($dynamic_segment));
    $this->subscribers_ids_loader
      ->expects($this->once())
      ->method('load')
      ->with($this->equalTo($dynamic_segment), $ids)
      ->will($this->returnValue([new Subscriber()]));
    $segment = DynamicSegment::create();
    $segment->type = DynamicSegment::TYPE_DYNAMIC;
    $segment->id = 3;
    $result = $this->subscribers_in_segments_finder->findSubscribersInSegment($segment, $ids);
    expect($result)->count(1);
  }


  public function testGetSubscriberIdsInSegmentReturnsEmptyIfNotDynamic() {
    $this->single_segment_loader
      ->expects($this->never())
      ->method('load');
    $this->subscribers_ids_loader
      ->expects($this->never())
      ->method('load');
    $segment = DynamicSegment::create();
    $segment->type = DynamicSegment::TYPE_DEFAULT;
    $result = $this->subscribers_in_segments_finder->getSubscriberIdsInSegment($segment);
    expect($result)->count(0);
  }

  public function testGetSubscriberIdsInSegmentReturnsSubscribers() {
    $dynamic_segment = DynamicSegment::create();
    $dynamic_segment->hydrate([
      'name' => 'segment 2',
      'description' => '',
    ]);
    $subscriber1 = Subscriber::create();
    $subscriber1->hydrate(['id' => 1]);
    $subscriber2 = Subscriber::create();
    $subscriber2->hydrate(['id' => 2]);
    $this->single_segment_loader
      ->expects($this->once())
      ->method('load')
      ->with($this->equalTo(3))
      ->will($this->returnValue($dynamic_segment));
    $this->subscribers_ids_loader
      ->expects($this->once())
      ->method('load')
      ->with($this->equalTo($dynamic_segment))
      ->will($this->returnValue([$subscriber1, $subscriber2]));
    $segment = DynamicSegment::create();
    $segment->type = DynamicSegment::TYPE_DYNAMIC;
    $segment->id = 3;
    $result = $this->subscribers_in_segments_finder->getSubscriberIdsInSegment($segment);
    expect($result)->count(2);
    expect($result[0]['id'])->equals(1);
    expect($result[1]['id'])->equals(2);
  }

}
