<?php

namespace MailPoet\DynamicSegments\FreePluginConnectors;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Models\DynamicSegment;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class AddToNewslettersSegmentsTest extends \MailPoetTest {

  public function testItReturnsOriginalArray() {
    $dynamicSegment = DynamicSegment::create();
    $dynamicSegment->hydrate([
      'name' => 'segment1',
      'description' => '',
    ]);

    $segmentLoader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\Loader', ['load' => Expected::once(function () {
      return [];
    })]);

    $subscribersCountLoader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount', ['getSubscribersCount' => Expected::never()]);

    $filter = new AddToNewslettersSegments($segmentLoader, $subscribersCountLoader);
    $result = $filter->add([$dynamicSegment]);
    expect($result)->equals([$dynamicSegment]);
  }

  public function testItAddsDynamicSegments() {
    $dynamicSegment = DynamicSegment::create();
    $dynamicSegment->hydrate([
      'name' => 'segment2',
      'description' => '',
      'id' => 1,
    ]);

    $segmentLoader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\Loader', ['load' => Expected::once(function () use ($dynamicSegment) {
      return [$dynamicSegment];
    })]);

    /** @var \MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount|MockObject $subscribers_count_loader */
    $subscribersCountLoader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount', ['getSubscribersCount']);
    $subscribersCountLoader
      ->expects($this->once())
      ->method('getSubscribersCount')
      ->with($this->equalTo($dynamicSegment))
      ->will($this->returnValue(4));

    $filter = new AddToNewslettersSegments($segmentLoader, $subscribersCountLoader);
    $result = $filter->add([]);

    expect($result)->count(1);
    expect($result[0])->equals([
      'id' => 1,
      'name' => 'segment2',
      'subscribers' => 4,
      'deleted_at' => null,
    ]);
  }
}
