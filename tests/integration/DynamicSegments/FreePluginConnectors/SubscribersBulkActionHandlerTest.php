<?php

namespace MailPoet\Premium\DynamicSegments\FreePluginConnectors;

use MailPoet\Premium\Models\DynamicSegment;

class SubscribersBulkActionHandlerTest extends \MailPoetTest {

  function testItReturnsNullWithUnknownSegment() {
    $segment = [
      'name' => 'name',
      'description' => 'desc',
      'type' => 'unknown',
    ];
    $handler = new SubscribersBulkActionHandler();
    $result = $handler->apply($segment, [
      'listing' => ['filter' => ['segment' => 5]],
      'action' => 'trash',
    ]);
    expect($result)->null();
  }

  function testItReturnsDataForDynamicSegment() {
    $segment = DynamicSegment::createOrUpdate([
      'name' => 'name',
      'description' => 'desc',
      'type' => DynamicSegment::TYPE_DYNAMIC,
    ]);
    $handler = new SubscribersBulkActionHandler();
    $result = $handler->apply($segment->asArray(), [
      'listing' => ['filter' => ['segment' => $segment->id()]],
      'action' => 'trash',
    ]);
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
