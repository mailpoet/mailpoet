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
  public $woocommerce_helper;
  public $woocommerce_segment;
  public function _before() {
    $this->woocommerce_segment = $this->createMock(WooCommerceSegment::class);
    $this->woocommerce_helper = $this->createMock(WooCommerceHelper::class);
    $this->worker = new WooCommerceSync($this->woocommerce_segment, $this->woocommerce_helper);
  }

  public function testItWillNotRunIfWooCommerceIsDisabled() {
    $this->woocommerce_helper->method('isWooCommerceActive')
      ->willReturn(false);
    expect($this->worker->checkProcessingRequirements())->false();
  }

  public function testItWillRunIfWooCommerceIsEnabled() {
    $this->woocommerce_helper->method('isWooCommerceActive')
      ->willReturn(true);
    expect($this->worker->checkProcessingRequirements())->true();
  }

  public function testItCallsWooCommerceSync() {
    $this->woocommerce_segment->expects($this->once())
      ->method('synchronizeCustomers');
    $task = $this->createScheduledTask();
    expect($this->worker->processTaskStrategy($task, microtime(true)))->equals(true);
  }

  private function createScheduledTask() {
    $task = ScheduledTask::create();
    $task->type = WooCommerceSync::TASK_TYPE;
    $task->status = null;
    $task->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->save();
    return $task;
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
