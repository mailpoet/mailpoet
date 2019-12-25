<?php

namespace MailPoet\DynamicSegments\FreePluginConnectors;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Models\DynamicSegment;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class AddToSubscribersFiltersTest extends \MailPoetTest {

  public function testItReturnsOriginalArray() {
    $original_segment = [
      'label' => 'segment1',
      'value' => '',
    ];

    $segment_loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\Loader', ['load' => Expected::once(function () {
      return [];
    })]);

    $subscribers_count_loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount', ['getSubscribersCount' => Expected::never()]);

    $filter = new AddToSubscribersFilters($segment_loader, $subscribers_count_loader);
    $result = $filter->add([$original_segment]);
    expect($result)->equals([$original_segment]);
  }

  public function testItAddsDynamicSegments() {
    $dynamic_segment = DynamicSegment::create();
    $dynamic_segment->hydrate([
      'name' => 'segment2',
      'description' => '',
      'id' => 1,
    ]);

    $segment_loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\Loader', ['load' => Expected::once(function () use ($dynamic_segment) {
      return [$dynamic_segment];
    })]);

    /** @var \MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount|MockObject $subscribers_count_loader */
    $subscribers_count_loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount', ['getSubscribersCount']);
    $subscribers_count_loader
      ->expects($this->once())
      ->method('getSubscribersCount')
      ->with($this->equalTo($dynamic_segment))
      ->will($this->returnValue(4));

    $filter = new AddToSubscribersFilters($segment_loader, $subscribers_count_loader);
    $result = $filter->add([]);

    expect($result)->count(1);
    expect($result[0])->equals([
      'label' => 'segment2 (4)',
      'value' => 1,
    ]);
  }

  public function testItSortsTheResult() {
    $dynamic_segment1 = DynamicSegment::create();
    $dynamic_segment1->hydrate([
      'name' => 'segment b',
      'description' => '',
      'id' => '1',
    ]);
    $dynamic_segment2 = DynamicSegment::create();
    $dynamic_segment2->hydrate([
      'name' => 'segment a',
      'description' => '',
      'id' => '2',
    ]);

    $segment_loader = Stub::makeEmpty(
      '\MailPoet\DynamicSegments\Persistence\Loading\Loader',
      [
        'load' => Expected::once(function () use ($dynamic_segment1, $dynamic_segment2) {
          return [$dynamic_segment1, $dynamic_segment2];
        }),
      ]
    );

    /** @var \MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount|MockObject $subscribers_count_loader */
    $subscribers_count_loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount', ['getSubscribersCount']);
    $subscribers_count_loader
      ->expects($this->exactly(2))
      ->method('getSubscribersCount')
      ->will($this->returnValue(4));

    $filter = new AddToSubscribersFilters($segment_loader, $subscribers_count_loader);
    $result = $filter->add([
      ['value' => '', 'label' => 'Special segment filter'],
      ['value' => '3', 'label' => 'segment c'],
    ]);

    expect($result)->count(4);
    expect($result[0]['value'])->equals('');
    expect($result[1]['value'])->equals('2');
    expect($result[2]['value'])->equals('1');
    expect($result[3]['value'])->equals('3');
  }
}
