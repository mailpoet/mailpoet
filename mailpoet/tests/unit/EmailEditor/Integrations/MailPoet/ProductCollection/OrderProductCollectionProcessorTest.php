<?php declare(strict_types = 1);

namespace unit\EmailEditor\Integrations\MailPoet\ProductCollection;

use MailPoet\EmailEditor\Integrations\MailPoet\ProductCollection\OrderProductCollectionProcessor;

class OrderProductCollectionProcessorTest extends \MailPoetUnitTest {
  /** @var OrderProductCollectionProcessor */
  private $processor;

  public function _before(): void {
    parent::_before();
    $this->processor = new OrderProductCollectionProcessor();
  }

  public function testCreateBlocksFilterReturnsNullWithoutAnOrder(): void {
    $this->assertNull($this->processor->createBlocksFilter([]));
    $this->assertNull($this->processor->createBlocksFilter(['order' => null]));
    $this->assertNull($this->processor->createBlocksFilter(['order' => new \stdClass()]));
  }
}
