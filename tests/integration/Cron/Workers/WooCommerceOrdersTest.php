<?php

namespace MailPoet\Test\Cron\Workers;

use DateTime;
use MailPoet\Cron\Workers\WooCommerceOrders;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class WooCommerceOrdersTest extends \MailPoetTest {
  /** @var WooCommerceHelper */
  private $woocommerce_helper;

  /** @var WooCommercePurchases */
  private $woocommerce_purchases;

  /** @var WooCommerceOrders */
  private $worker;

  function _before() {
    $this->cleanup();
    $this->woocommerce_helper = $this->createMock(WooCommerceHelper::class);
    $this->woocommerce_purchases = $this->createMock(WooCommercePurchases::class);
    $this->worker = new WooCommerceOrders($this->woocommerce_helper, $this->woocommerce_purchases, microtime(true));
  }

  function testItDoesNotRunIfWooCommerceIsDisabled() {
    $this->woocommerce_helper->method('isWooCommerceActive')->willReturn(false);
    expect($this->worker->checkProcessingRequirements())->false();

    $this->worker->process();
    $tasks = ScheduledTask::where('type', WooCommerceOrders::TASK_TYPE)->findMany();
    expect($tasks)->isEmpty();
  }

  function testItRunsIfWooCommerceIsEnabled() {
    $this->woocommerce_helper->method('isWooCommerceActive')->willReturn(true);
    expect($this->worker->checkProcessingRequirements())->true();

    $this->worker->process();
    $tasks = ScheduledTask::where('type', WooCommerceOrders::TASK_TYPE)->findMany();
    expect($tasks)->count(1);
  }

  function testItRunsOnlyOnce() {
    $this->woocommerce_helper->method('isWooCommerceActive')->willReturn(true);
    $this->woocommerce_helper->method('wcGetOrders')->willReturn([]);

    // 1. schedule
    expect($this->worker->checkProcessingRequirements())->true();
    $this->worker->process();
    $task = ScheduledTask::where('type', WooCommerceOrders::TASK_TYPE)->findOne();
    expect($task->status)->equals(ScheduledTask::STATUS_SCHEDULED);

    // 2. prepare
    expect($this->worker->checkProcessingRequirements())->true();
    $this->worker->process();
    $task = ScheduledTask::where('type', WooCommerceOrders::TASK_TYPE)->findOne();
    expect($task->status)->null(); // null means 'running'

    // 3. run
    expect($this->worker->checkProcessingRequirements())->true();
    $this->worker->process();
    $task = ScheduledTask::where('type', WooCommerceOrders::TASK_TYPE)->findOne();
    expect($task->status)->equals(ScheduledTask::STATUS_COMPLETED);

    // 4. complete (do not schedule again)
    expect($this->worker->checkProcessingRequirements())->false();
    $this->worker->process();
    $task = ScheduledTask::where('type', WooCommerceOrders::TASK_TYPE)->findOne();
    expect($task->status)->equals(ScheduledTask::STATUS_COMPLETED);

    $tasks = ScheduledTask::where('type', WooCommerceOrders::TASK_TYPE)->findMany();
    expect($tasks)->count(1);
  }

  function testItTracksOrders() {
    $this->woocommerce_helper->method('isWooCommerceActive')->willReturn(true);
    $this->woocommerce_helper->method('wcGetOrders')->willReturn([1, 2, 3]);
    $this->createClick();

    $this->woocommerce_purchases->expects($this->exactly(3))->method('trackPurchase');

    $this->worker->process(); // schedule
    $this->worker->process(); // prepare
    $this->worker->process(); // run

    $tasks = ScheduledTask::where('type', WooCommerceOrders::TASK_TYPE)->findMany();
    expect($tasks)->count(1);
    expect($tasks[0]->status)->equals(null);  // null means 'running'
  }


  function testItContinuesFromLastId() {
    $this->woocommerce_helper->method('isWooCommerceActive')->willReturn(true);
    $this->woocommerce_helper->method('wcGetOrders')->willReturnOnConsecutiveCalls([1, 2, 3], [4, 5], []);
    $this->createClick();

    $this->woocommerce_purchases->expects($this->exactly(5))->method('trackPurchase');

    $this->worker->process(); // schedule
    $this->worker->process(); // prepare
    $this->worker->process(); // run for 1, 2, 3

    $task = ScheduledTask::where('type', WooCommerceOrders::TASK_TYPE)->findOne();
    expect($task->getMeta())->equals(['last_id' => 3]);

    $this->worker->process(); // run for 4, 5

    $task = ScheduledTask::where('type', WooCommerceOrders::TASK_TYPE)->findOne();
    expect($task->getMeta())->equals(['last_id' => 5]);

    $this->worker->process(); // complete

    $tasks = ScheduledTask::where('type', WooCommerceOrders::TASK_TYPE)->findMany();
    expect($tasks)->count(1);
    expect($tasks[0]->status)->equals(ScheduledTask::STATUS_COMPLETED);
  }

  function _after() {
    $this->cleanup();
  }


  private function createClick($created_days_ago = 5) {
    $click = StatisticsClicks::create();
    $click->newsletter_id = 1;
    $click->subscriber_id = 1;
    $click->queue_id = 1;
    $click->link_id = 1;
    $click->count = 1;

    $timestamp = new DateTime("-$created_days_ago days");
    $click->created_at = $timestamp->format('Y-m-d H:i:s');
    $click->updated_at = $timestamp->format('Y-m-d H:i:s');
    return $click->save();
  }

  private function cleanup() {
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
  }
}
