<?php

namespace MailPoet\DynamicSegments\FreePluginConnectors;

use Codeception\Util\Stub;
use MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader;
use MailPoet\DynamicSegments\Persistence\Loading\SubscribersIds;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class SendingNewslettersSubscribersFinderTest extends \MailPoetTest {

  /** @var SingleSegmentLoader|MockObject */
  private $singleSegmentLoader;

  /** @var SubscribersIds|MockObject */
  private $subscribersIdsLoader;

  /** @var SendingNewslettersSubscribersFinder */
  private $subscribersInSegmentsFinder;

  public function _before() {
    $this->singleSegmentLoader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader');
    $this->subscribersIdsLoader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SubscribersIds');
    $this->subscribersInSegmentsFinder = new SendingNewslettersSubscribersFinder($this->singleSegmentLoader, $this->subscribersIdsLoader);
  }

  public function testFindSubscribersInSegmentReturnsEmptyIfNotDynamic() {
    $this->singleSegmentLoader
      ->expects($this->never())
      ->method('load');
    $this->subscribersIdsLoader
      ->expects($this->never())
      ->method('load');
    $segment = Segment::create();
    $segment->type = Segment::TYPE_DEFAULT;
    $segment->id = '3';
    $result = $this->subscribersInSegmentsFinder->findSubscribersInSegment($segment, []);
    expect($result)->count(0);
  }

  public function testFindSubscribersInSegmentReturnsSubscribers() {
    $dynamicSegment = DynamicSegment::create();
    $dynamicSegment->hydrate([
      'name' => 'segment 1',
      'description' => '',
    ]);
    $ids = [1, 2, 3];
    $this->singleSegmentLoader
      ->expects($this->once())
      ->method('load')
      ->with($this->equalTo(3))
      ->will($this->returnValue($dynamicSegment));
    $this->subscribersIdsLoader
      ->expects($this->once())
      ->method('load')
      ->with($this->equalTo($dynamicSegment), $ids)
      ->will($this->returnValue([new Subscriber()]));
    $segment = DynamicSegment::create();
    $segment->type = DynamicSegment::TYPE_DYNAMIC;
    $segment->id = 3;
    $result = $this->subscribersInSegmentsFinder->findSubscribersInSegment($segment, $ids);
    expect($result)->count(1);
  }


  public function testGetSubscriberIdsInSegmentReturnsEmptyIfNotDynamic() {
    $this->singleSegmentLoader
      ->expects($this->never())
      ->method('load');
    $this->subscribersIdsLoader
      ->expects($this->never())
      ->method('load');
    $segment = DynamicSegment::create();
    $segment->type = DynamicSegment::TYPE_DEFAULT;
    $result = $this->subscribersInSegmentsFinder->getSubscriberIdsInSegment($segment);
    expect($result)->count(0);
  }

  public function testGetSubscriberIdsInSegmentReturnsSubscribers() {
    $dynamicSegment = DynamicSegment::create();
    $dynamicSegment->hydrate([
      'name' => 'segment 2',
      'description' => '',
    ]);
    $subscriber1 = Subscriber::create();
    $subscriber1->hydrate(['id' => 1]);
    $subscriber2 = Subscriber::create();
    $subscriber2->hydrate(['id' => 2]);
    $this->singleSegmentLoader
      ->expects($this->once())
      ->method('load')
      ->with($this->equalTo(3))
      ->will($this->returnValue($dynamicSegment));
    $this->subscribersIdsLoader
      ->expects($this->once())
      ->method('load')
      ->with($this->equalTo($dynamicSegment))
      ->will($this->returnValue([$subscriber1, $subscriber2]));
    $segment = DynamicSegment::create();
    $segment->type = DynamicSegment::TYPE_DYNAMIC;
    $segment->id = 3;
    $result = $this->subscribersInSegmentsFinder->getSubscriberIdsInSegment($segment);
    expect($result)->count(2);
    expect($result[0]['id'])->equals(1);
    expect($result[1]['id'])->equals(2);
  }

}
