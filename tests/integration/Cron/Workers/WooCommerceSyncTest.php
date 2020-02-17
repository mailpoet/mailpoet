<?php

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Models\ScheduledTask;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class WooCommerceSyncTest extends \MailPoetTest {
  public $worker;
  public $woocommerceHelper;
  public $woocommerceSegment;

  public function _before() {
    $this->woocommerceSegment = $this->createMock(WooCommerceSegment::class);
    $this->woocommerceHelper = $this->createMock(WooCommerceHelper::class);
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
    $this->woocommerceSegment->expects($this->once())
      ->method('synchronizeCustomers');
    $task = $this->createScheduledTask();
    expect($this->worker->processTaskStrategy($task, microtime(true)))->equals(true);
  }

  private function createScheduledTask() {
    $task = ScheduledTask::create();
    $task->type = WooCommerceSync::TASK_TYPE;
    $task->status = null;
    $task->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->save();
    return $task;
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
