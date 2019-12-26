<?php

namespace MailPoet\DynamicSegments\FreePluginConnectors;

use MailPoet\Models\DynamicSegment;
use MailPoetVendor\Idiorm\ORM;

class SubscribersBulkActionHandlerTest extends \MailPoetTest {

  public function testItReturnsNullWithUnknownSegment() {
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

  public function testItReturnsDataForDynamicSegment() {
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
