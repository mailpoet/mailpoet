<?php

namespace MailPoet\Premium\DynamicSegments\FreePluginConnectors;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Premium\Models\DynamicSegment;

class AddToNewslettersSegmentsTest extends \MailPoetTest {

  function testItReturnsOriginalArray() {
    $dynamic_segment = DynamicSegment::create();
    $dynamic_segment->hydrate([
      'name' => 'segment1',
      'description' => '',
    ]);

    $segment_loader = Stub::makeEmpty('\MailPoet\Premium\DynamicSegments\Persistence\Loading\Loader', ['load' => Expected::once(function () {
      return [];
    })]);

    $subscribers_count_loader = Stub::makeEmpty('\MailPoet\Premium\DynamicSegments\Persistence\Loading\SubscribersCount', ['getSubscribersCount' => Expected::never()]);

    $filter = new AddToNewslettersSegments($segment_loader, $subscribers_count_loader);
    $result = $filter->add([$dynamic_segment]);
    expect($result)->equals([$dynamic_segment]);
  }

  function testItAddsDynamicSegments() {
    $dynamic_segment = DynamicSegment::create();
    $dynamic_segment->hydrate([
      'name' => 'segment2',
      'description' => '',
      'id' => 1,
    ]);

    $segment_loader = Stub::makeEmpty('\MailPoet\Premium\DynamicSegments\Persistence\Loading\Loader', ['load' => Expected::once(function () use ($dynamic_segment) {
      return [$dynamic_segment];
    })]);

    $subscribers_count_loader = Stub::makeEmpty('\MailPoet\Premium\DynamicSegments\Persistence\Loading\SubscribersCount', ['getSubscribersCount']);
    $subscribers_count_loader
      ->expects($this->once())
      ->method('getSubscribersCount')
      ->with($this->equalTo($dynamic_segment))
      ->will($this->returnValue(4));

    $filter = new AddToNewslettersSegments($segment_loader, $subscribers_count_loader);
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
