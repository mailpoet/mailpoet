<?php

namespace MailPoet\Test\Cron\Workers;

use DateTime;
use MailPoet\Cron\Workers\WooCommercePastOrders;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsWooCommercePurchases;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class WooCommerceOrdersTest extends \MailPoetTest {
  /** @var WooCommerceHelper */
  private $woocommerce_helper;

  /** @var WooCommercePurchases */
  private $woocommerce_purchases;

  /** @var WooCommercePastOrders */
  private $worker;

  function _before() {
    $this->cleanup();
    $this->woocommerce_helper = $this->createMock(WooCommerceHelper::class);
    $this->woocommerce_purchases = $this->createMock(WooCommercePurchases::class);

    $this->worker = new WooCommercePastOrders($this->woocommerce_helper, $this->woocommerce_purchases, microtime(true));
  }

  function testItDoesNotRunIfWooCommerceIsDisabled() {
    $this->woocommerce_helper->method('isWooCommerceActive')->willReturn(false);
    expect($this->worker->checkProcessingRequirements())->false();

    $this->worker->process();
    $tasks = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findMany();
    expect($tasks)->isEmpty();
  }

  function testItRunsIfWooCommerceIsEnabled() {
    $this->woocommerce_helper->method('isWooCommerceActive')->willReturn(true);
    expect($this->worker->checkProcessingRequirements())->true();

    $this->worker->process();
    $tasks = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findMany();
    expect($tasks)->count(1);
  }

  function testItRunsOnlyOnce() {
    $this->woocommerce_helper->method('isWooCommerceActive')->willReturn(true);
    $this->woocommerce_helper->method('wcGetOrders')->willReturn([]);

    // 1. schedule
    expect($this->worker->checkProcessingRequirements())->true();
    $this->worker->process();
    $task = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findOne();
    expect($task->status)->equals(ScheduledTask::STATUS_SCHEDULED);

    // 2. prepare
    expect($this->worker->checkProcessingRequirements())->true();
    $this->worker->process();
    $task = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findOne();
    expect($task->status)->null(); // null means 'running'

    // 3. run
    expect($this->worker->checkProcessingRequirements())->true();
    $this->worker->process();
    $task = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findOne();
    expect($task->status)->equals(ScheduledTask::STATUS_COMPLETED);

    // 4. complete (do not schedule again)
    expect($this->worker->checkProcessingRequirements())->false();
    $this->worker->process();
    $task = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findOne();
    expect($task->status)->equals(ScheduledTask::STATUS_COMPLETED);

    $tasks = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findMany();
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

    $tasks = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findMany();
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

    $task = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findOne();
    expect($task->getMeta())->equals(['last_processed_id' => 3]);

    $this->worker->process(); // run for 4, 5

    $task = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findOne();
    expect($task->getMeta())->equals(['last_processed_id' => 5]);

    $this->worker->process(); // complete

    $tasks = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findMany();
    expect($tasks)->count(1);
    expect($tasks[0]->status)->equals(ScheduledTask::STATUS_COMPLETED);
  }

  function testItResetsPreviouslyTrackedOrders() {
    $this->woocommerce_helper->method('isWooCommerceActive')->willReturn(true);
    $this->woocommerce_helper->method('wcGetOrders')->willReturnOnConsecutiveCalls([1, 2], [3], [4]);
    $click = $this->createClick();

    $this->woocommerce_purchases->expects($this->exactly(4))->method('trackPurchase');

    // wrong data inserted by a past buggy version should be removed for each order
    // nothing new is inserted because we don't fully mock WC_Order (expect count 0)
    $this->createOrder(1, $click);
    $this->createOrder(2, $click);
    $this->worker->process(); // schedule
    $this->worker->process(); // prepare
    $this->worker->process(); // run for 1, 2
    expect(StatisticsWooCommercePurchases::findMany())->count(0);

    // don't remove data for unrelated orders (for order ID 4 row should not be removed)
    $this->createOrder(3, $click);
    $this->createOrder(4, $click);
    $this->worker->process(); // run for 3
    $purchase_stats = StatisticsWooCommercePurchases::findMany();
    expect($purchase_stats)->count(1);
    expect($purchase_stats[0]->order_id)->equals(4);

    // now row for order ID 4 should be removed as well
    $this->worker->process(); // run for 4
    expect(StatisticsWooCommercePurchases::findMany())->count(0);
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

  private function createOrder($id, StatisticsClicks $click) {
    $statistics = StatisticsWooCommercePurchases::create();
    $statistics->newsletter_id = $click->newsletter_id;
    $statistics->subscriber_id = $click->subscriber_id;
    $statistics->queue_id = $click->queue_id;
    $statistics->click_id = $click->id;
    $statistics->order_id = $id;
    $statistics->order_currency = 'EUR';
    $statistics->order_price_total = 123.0;
    $statistics->save();
  }

  private function cleanup() {
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
  }
}
