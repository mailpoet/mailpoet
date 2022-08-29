<?php

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Connection;

/**
 * @group woo
 */
class WooCommerceSyncTest extends \MailPoetTest {
  public $worker;
  public $woocommerceHelper;
  public $woocommerceSegment;
  public $connection;
  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  public function _before() {
    $this->woocommerceSegment = $this->createMock(WooCommerceSegment::class);
    $this->woocommerceHelper = $this->createMock(WooCommerceHelper::class);
    $this->connection = $this->createMock(Connection::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->worker = new WooCommerceSync($this->woocommerceSegment, $this->woocommerceHelper, $this->connection);
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
    $this->woocommerceSegment->expects($this->once())
      ->method('synchronizeCustomers')
      ->willReturn(1000);
    $task = $this->scheduledTaskFactory->create(
      WooCommerceSync::TASK_TYPE,
      null,
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))
    );
    expect($this->worker->processTaskStrategy($task, microtime(true)))->equals(true);
  }

  public function _after() {
    $this->truncateEntity(ScheduledTaskEntity::class);
  }
}
