<?php

namespace MailPoet\Premium\DynamicSegments\FreePluginConnectors;

use MailPoet\Premium\Models\DynamicSegment;

class SubscribersListingsHandlerFactoryTest extends \MailPoetTest {

  function testItReturnsNullWithUnknownSegment() {
    $segment = DynamicSegment::create();
    $segment->id = 1;
    $segment->name = 'name';
    $segment->type = 'unknown';
    $listings = new SubscribersListingsHandlerFactory();
    $result = $listings->get($segment, []);
    expect($result)->null();
  }

  function testItReturnsDataForDynamicSegment() {
    $segment = DynamicSegment::createOrUpdate([
      'name' => 'name',
      'description' => 'desc',
      'type' => DynamicSegment::TYPE_DYNAMIC,
    ]);
    $listings = new SubscribersListingsHandlerFactory();
    $result = $listings->get($segment, []);
    expect($result)->notNull();
  }

  function _before() {
    $this->cleanData();
  }

  function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    \ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
  }

}
