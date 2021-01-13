<?php

namespace MailPoet\Test\Cron\Workers;

use Codeception\Util\Stub;
use DateTime;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\Cron\Workers\WooCommercePastOrders;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsWooCommercePurchases;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoetVendor\Idiorm\ORM;
use PHPUnit\Framework\MockObject\MockObject;

class WooCommerceOrdersTest extends \MailPoetTest {
  /** @var MockObject */
  private $woocommerceHelper;

  /** @var MockObject */
  private $woocommercePurchases;

  /** @var WooCommercePastOrders */
  private $worker;

  /** @var CronWorkerRunner */
  private $cronWorkerRunner;

  public function _before() {
    $this->cleanup();
    $this->woocommerceHelper = $this->createMock(WooCommerceHelper::class);
    $this->woocommercePurchases = $this->createMock(WooCommercePurchases::class);

    $this->worker = new WooCommercePastOrders($this->woocommerceHelper, $this->woocommercePurchases);
    $this->cronWorkerRunner = Stub::copy($this->diContainer->get(CronWorkerRunner::class), [
      'timer' => microtime(true), // reset timer to avoid timeout during full test suite run
    ]);
  }

  public function testItDoesNotRunIfWooCommerceIsDisabled() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(false);
    expect($this->worker->checkProcessingRequirements())->false();

    $this->cronWorkerRunner->run($this->worker);
    $tasks = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findMany();
    expect($tasks)->isEmpty();
  }

  public function testItRunsIfWooCommerceIsEnabled() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(true);
    expect($this->worker->checkProcessingRequirements())->true();

    $this->cronWorkerRunner->run($this->worker);
    $tasks = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findMany();
    expect($tasks)->count(1);
  }

  public function testItRunsOnlyOnce() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $this->woocommerceHelper->method('wcGetOrders')->willReturn([]);

    // 1. schedule
    expect($this->worker->checkProcessingRequirements())->true();
    $this->cronWorkerRunner->run($this->worker);
    $task = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findOne();
    assert($task instanceof ScheduledTask);
    expect($task->status)->equals(ScheduledTask::STATUS_SCHEDULED);

    // 2. prepare
    expect($this->worker->checkProcessingRequirements())->true();
    $this->cronWorkerRunner->run($this->worker);
    $task = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findOne();
    assert($task instanceof ScheduledTask);
    expect($task->status)->null(); // null means 'running'

    // 3. run
    expect($this->worker->checkProcessingRequirements())->true();
    $this->cronWorkerRunner->run($this->worker);
    $task = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findOne();
    assert($task instanceof ScheduledTask);
    expect($task->status)->equals(ScheduledTask::STATUS_COMPLETED);

    // 4. complete (do not schedule again)
    expect($this->worker->checkProcessingRequirements())->false();
    $this->cronWorkerRunner->run($this->worker);
    $task = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findOne();
    assert($task instanceof ScheduledTask);
    expect($task->status)->equals(ScheduledTask::STATUS_COMPLETED);

    $tasks = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findMany();
    expect($tasks)->count(1);
  }

  public function testItTracksOrders() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $this->woocommerceHelper->method('wcGetOrders')->willReturn([1, 2, 3]);
    $this->createClick();

    $this->woocommercePurchases->expects($this->exactly(3))->method('trackPurchase');

    $this->cronWorkerRunner->run($this->worker); // schedule
    $this->cronWorkerRunner->run($this->worker); // prepare
    $this->cronWorkerRunner->run($this->worker); // run

    $tasks = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findMany();
    expect($tasks)->count(1);
    expect($tasks[0]->status)->equals(null);  // null means 'running'
  }

  public function testItContinuesFromLastId() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $this->woocommerceHelper->method('wcGetOrders')->willReturnOnConsecutiveCalls([1, 2, 3], [4, 5], []);
    $this->createClick();

    $this->woocommercePurchases->expects($this->exactly(5))->method('trackPurchase');

    $this->cronWorkerRunner->run($this->worker); // schedule
    $this->cronWorkerRunner->run($this->worker); // prepare
    $this->cronWorkerRunner->run($this->worker); // run for 1, 2, 3

    $task = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findOne();
    assert($task instanceof ScheduledTask);
    expect($task->getMeta())->equals(['last_processed_id' => 3]);

    $this->cronWorkerRunner->run($this->worker); // run for 4, 5

    $task = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findOne();
    assert($task instanceof ScheduledTask);
    expect($task->getMeta())->equals(['last_processed_id' => 5]);

    $this->cronWorkerRunner->run($this->worker); // complete

    $tasks = ScheduledTask::where('type', WooCommercePastOrders::TASK_TYPE)->findMany();
    expect($tasks)->count(1);
    expect($tasks[0]->status)->equals(ScheduledTask::STATUS_COMPLETED);
  }

  public function testItResetsPreviouslyTrackedOrders() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $this->woocommerceHelper->method('wcGetOrders')->willReturnOnConsecutiveCalls([1, 2], [3], [4]);
    $click = $this->createClick();

    $this->woocommercePurchases->expects($this->exactly(4))->method('trackPurchase');

    // wrong data inserted by a past buggy version should be removed for each order
    // nothing new is inserted because we don't fully mock WC_Order (expect count 0)
    $this->createOrder(1, $click);
    $this->createOrder(2, $click);
    $this->cronWorkerRunner->run($this->worker); // schedule
    $this->cronWorkerRunner->run($this->worker); // prepare
    $this->cronWorkerRunner->run($this->worker); // run for 1, 2
    expect(StatisticsWooCommercePurchases::findMany())->count(0);

    // don't remove data for unrelated orders (for order ID 4 row should not be removed)
    $this->createOrder(3, $click);
    $this->createOrder(4, $click);
    $this->cronWorkerRunner->run($this->worker); // run for 3
    $purchaseStats = StatisticsWooCommercePurchases::findMany();
    expect($purchaseStats)->count(1);
    expect($purchaseStats[0]->order_id)->equals(4);

    // now row for order ID 4 should be removed as well
    $this->cronWorkerRunner->run($this->worker); // run for 4
    expect(StatisticsWooCommercePurchases::findMany())->count(0);
  }

  public function _after() {
    $this->cleanup();
  }

  private function createClick($createdDaysAgo = 5) {
    $click = StatisticsClicks::create();
    $click->newsletterId = 1;
    $click->subscriberId = 1;
    $click->queueId = 1;
    $click->linkId = 1;
    $click->count = 1;

    $timestamp = new DateTime("-$createdDaysAgo days");
    $click->createdAt = $timestamp->format('Y-m-d H:i:s');
    $click->updatedAt = $timestamp->format('Y-m-d H:i:s');
    return $click->save();
  }

  private function createOrder($id, StatisticsClicks $click) {
    $statistics = StatisticsWooCommercePurchases::create();
    $statistics->newsletterId = $click->newsletterId;
    $statistics->subscriberId = $click->subscriberId;
    $statistics->queueId = $click->queueId;
    $statistics->clickId = (int)$click->id;
    $statistics->orderId = $id;
    $statistics->orderCurrency = 'EUR';
    $statistics->orderPriceTotal = 123.0;
    $statistics->save();
  }

  private function cleanup() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
  }
}
