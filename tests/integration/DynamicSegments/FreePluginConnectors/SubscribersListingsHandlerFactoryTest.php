<?php

namespace MailPoet\DynamicSegments\FreePluginConnectors;

use MailPoet\Models\DynamicSegment;
use MailPoetVendor\Idiorm\ORM;

class SubscribersListingsHandlerFactoryTest extends \MailPoetTest {

  public function testItReturnsNullWithUnknownSegment() {
    $segment = DynamicSegment::create();
    $segment->id = 1;
    $segment->name = 'name';
    $segment->type = 'unknown';
    $listings = new SubscribersListingsHandlerFactory();
    $result = $listings->get($segment, ['filter' => ['segment' => null]]);
    expect($result)->null();
  }

  public function testItReturnsDataForDynamicSegment() {
    $segment = DynamicSegment::createOrUpdate([
      'name' => 'name',
      'description' => 'desc',
      'type' => DynamicSegment::TYPE_DYNAMIC,
    ]);
    $listings = new SubscribersListingsHandlerFactory();
    $result = $listings->get($segment, ['filter' => ['segment' => null]]);
    expect($result)->notNull();
  }

  public function _before() {
    $this->cleanData();
  }

  public function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
  }

}
