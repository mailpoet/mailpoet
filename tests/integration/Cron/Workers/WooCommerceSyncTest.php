<?php
namespace MailPoet\Test\Cron\Workers;

use Codeception\Util\Stub;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Models\ScheduledTask;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class WooCommerceSyncTest extends \MailPoetTest {
  function _before() {
    $this->woocommerce_segment = $this->createMock(WooCommerceSegment::class);
    $this->woocommerce_helper = $this->createMock(WooCommerceHelper::class);
    $this->worker = new WooCommerceSync($this->woocommerce_segment, $this->woocommerce_helper, microtime(true));
  }

  function testItWillNotRunIfWooCommerceIsDisabled() {
    $this->woocommerce_helper->method('isWooCommerceActive')
      ->willReturn(false);
    expect($this->worker->checkProcessingRequirements())->false();
  }

  function testItWillRunIfWooCommerceIsEnabled() {
    $this->woocommerce_helper->method('isWooCommerceActive')
      ->willReturn(true);
    expect($this->worker->checkProcessingRequirements())->true();
  }

  function testItCallsWooCommerceSync() {
    $this->woocommerce_segment->expects($this->once())
      ->method('synchronizeCustomers');
    $task = Stub::make(ScheduledTask::class);
    expect($this->worker->processTaskStrategy($task))->equals(true);
  }
}