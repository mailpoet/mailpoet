<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\BuysAProductTrigger;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;

/**
 * @group woo
 */
class BuysAProductTriggerTest extends \MailPoetTest {

  /** @var BuysAProductTrigger */
  private $testee;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  public function _before() {
    $this->testee = $this->diContainer->get(BuysAProductTrigger::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
  }

  public function testItDoesRunOnlyOncePerOrder() {

    $product1 = $this->createProduct('product 1');
    $product2 = $this->createProduct('product 2');
    $automation = $this->createAutomation([$product1], 'completed');
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $order = $this->createOrder([$product1, $product2]);

    $this->testee->registerHooks();
    $order->set_status('on-hold');
    $order->save();
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $order->set_status('completed');
    $order->save();
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation));

    $order->set_status('on-hold');
    $order->save();
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $order->set_status('completed');
    $order->save();
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
  }

  public function testItDoesRunOnAnyStatus() {

    $product1 = $this->createProduct('product 1');
    $product2 = $this->createProduct('product 2');
    $automation = $this->createAutomation([$product1], 'any');
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $order = $this->createOrder([$product1, $product2]);

    $this->testee->registerHooks();
    $order->set_status('on-hold');
    $order->save();
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
  }

  public function testItDoesNotRunWhenProductsDoNotMatch() {

    $product1 = $this->createProduct('product 1');
    $product2 = $this->createProduct('product 2');
    $automation = $this->createAutomation([$product2], 'completed');
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $order = $this->createOrder([$product1]);

    $this->testee->registerHooks();
    $order->set_status('completed');
    $order->save();
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
  }

  private function createAutomation($productIds, $status): Automation {
    $trigger = new Step(
      'trigger',
      Step::TYPE_TRIGGER,
      BuysAProductTrigger::KEY,
      [
        'product_ids' => $productIds,
        'to' => $status,
      ],
      [new NextStep('action')]
    );
    $action = new Step(
      'action',
      Step::TYPE_ACTION,
      'core:delay',
      [
        'delay' => 1,
        'delay_type' => 'MINUTES',
      ],
      []
    );
    return (new AutomationFactory())
      ->withStatusActive()
      ->addStep($trigger)
      ->addStep($action)
      ->create();
  }

  private function createProduct(string $name, float $price = 1.99): int {

    $product = new \WC_Product();
    $product->set_name($name);
    $product->set_price((string)$price);
    $product->save();
    return $product->get_id();
  }

  /**
   * @param int[] $productIds
   * @param string $billingEmail
   * @return \WC_Order
   * @throws \WC_Data_Exception
   */
  private function createOrder(array $productIds, string $billingEmail = null): \WC_Order {

    $order = new \WC_Order();
    $order->set_billing_email($billingEmail ?? uniqid() . '@example.com');
    foreach ($productIds as $id) {
      $order->add_product(new \WC_Product($id), 1);
    }

    $order->save();
    return $order;
  }
}
