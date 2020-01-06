<?php

namespace MailPoet\DynamicSegments\FreePluginConnectors;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Models\DynamicSegment;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class AddToSubscribersFiltersTest extends \MailPoetTest {

  public function testItReturnsOriginalArray() {
    $originalSegment = [
      'label' => 'segment1',
      'value' => '',
    ];

    $segmentLoader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\Loader', ['load' => Expected::once(function () {
      return [];
    })]);

    $subscribersCountLoader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount', ['getSubscribersCount' => Expected::never()]);

    $filter = new AddToSubscribersFilters($segmentLoader, $subscribersCountLoader);
    $result = $filter->add([$originalSegment]);
    expect($result)->equals([$originalSegment]);
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

    /** @var \MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount|MockObject $subscribersCountLoader */
    $subscribersCountLoader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount', ['getSubscribersCount']);
    $subscribersCountLoader
      ->expects($this->once())
      ->method('getSubscribersCount')
      ->with($this->equalTo($dynamicSegment))
      ->will($this->returnValue(4));

    $filter = new AddToSubscribersFilters($segmentLoader, $subscribersCountLoader);
    $result = $filter->add([]);

    expect($result)->count(1);
    expect($result[0])->equals([
      'label' => 'segment2 (4)',
      'value' => 1,
    ]);
  }

  public function testItSortsTheResult() {
    $dynamicSegment1 = DynamicSegment::create();
    $dynamicSegment1->hydrate([
      'name' => 'segment b',
      'description' => '',
      'id' => '1',
    ]);
    $dynamicSegment2 = DynamicSegment::create();
    $dynamicSegment2->hydrate([
      'name' => 'segment a',
      'description' => '',
      'id' => '2',
    ]);

    $segmentLoader = Stub::makeEmpty(
      '\MailPoet\DynamicSegments\Persistence\Loading\Loader',
      [
        'load' => Expected::once(function () use ($dynamicSegment1, $dynamicSegment2) {
          return [$dynamicSegment1, $dynamicSegment2];
        }),
      ]
    );

    /** @var \MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount|MockObject $subscribersCountLoader */
    $subscribersCountLoader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount', ['getSubscribersCount']);
    $subscribersCountLoader
      ->expects($this->exactly(2))
      ->method('getSubscribersCount')
      ->will($this->returnValue(4));

    $filter = new AddToSubscribersFilters($segmentLoader, $subscribersCountLoader);
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
