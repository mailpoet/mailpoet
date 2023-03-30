<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceSyncTest extends \MailPoetTest {
  public $worker;
  public $woocommerceHelper;
  public $woocommerceSegment;
  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  public function _before() {
    $this->woocommerceSegment = $this->createMock(WooCommerceSegment::class);
    $this->woocommerceHelper = $this->createMock(WooCommerceHelper::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->worker = new WooCommerceSync($this->woocommerceSegment, $this->woocommerceHelper);
  }

  public function testItWillNotRunIfWooCommerceIsDisabled() {
    $this->woocommerceHelper->method('isWooCommerceActive')
      ->willReturn(false);
    expect($this->worker->checkProcessingRequirements())->false();
  }

  public function testItWillRunIfWooCommerceIsEnabled() {
    $this->woocommerceHelper->method('isWooCommerceActive')
      ->willReturn(true);
    expect($this->worker->checkProcessingRequirements())->true();
  }

  public function testItCallsWooCommerceSync() {
    $this->tester->createWooCommerceOrder();

    $woocommerceHelper = $this->diContainer->get(WooCommerceHelper::class);
    $worker = new WooCommerceSync($this->woocommerceSegment, $woocommerceHelper);
    $this->woocommerceSegment->expects($this->once())
      ->method('synchronizeCustomers')
      ->with(0, $this->greaterThan(0), WooCommerceSync::BATCH_SIZE)
      ->willReturn(1000);
    $task = $this->scheduledTaskFactory->create(
      WooCommerceSync::TASK_TYPE,
      null,
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))
    );
    expect($worker->processTaskStrategy($task, microtime(true)))->equals(true);
  }

  public function _after() {
    parent::_after();
  }
}
