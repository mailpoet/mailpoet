<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\BuysFromACategoryTrigger;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;

/**
 * @group woo
 */
class BuysFromACategoryTriggerTest extends \MailPoetTest {

  /** @var BuysFromACategoryTrigger */
  private $testee;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  public function _before() {
    $this->testee = $this->diContainer->get(BuysFromACategoryTrigger::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
  }

  public function testItDoesRunOnlyOncePerOrder() {

    $category1 = $this->createProductCategory("testItDoesRunOnlyOncePerOrder Category 1");
    $category2 = $this->createProductCategory("testItDoesRunOnlyOncePerOrder Category 2");
    $product1 = $this->createProduct('product 1', $category1);
    $product2 = $this->createProduct('product 2', $category2);
    $automation = $this->createAutomation([$category1], 'completed');
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

    $category1 = $this->createProductCategory("testItDoesRunOnAnyStatus Category 1");
    $category2 = $this->createProductCategory("testItDoesRunOnAnyStatus Category 2");
    $product1 = $this->createProduct('product 1', $category1);
    $product2 = $this->createProduct('product 2', $category2);
    $automation = $this->createAutomation([$category1], 'any');
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $order = $this->createOrder([$product1, $product2]);

    $this->testee->registerHooks();
    $order->set_status('on-hold');
    $order->save();
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
  }

  public function testItDoesNotRunWhenCategoriesDoNotMatch() {

    $category1 = $this->createProductCategory("testItDoesNotRunWhenCategoriesDoNotMatch Category 1");
    $category2 = $this->createProductCategory("testItDoesNotRunWhenCategoriesDoNotMatch Category 2");
    $product1 = $this->createProduct('product 1', $category1);
    $product2 = $this->createProduct('product 2', $category2);
    $automation = $this->createAutomation([$category2], 'completed');
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $order = $this->createOrder([$product1]);

    $this->testee->registerHooks();
    $order->set_status('completed');
    $order->save();
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
  }

  private function createAutomation($categoryIds, $status): Automation {
    $trigger = new Step(
      'trigger',
      Step::TYPE_TRIGGER,
      BuysFromACategoryTrigger::KEY,
      [
        'category_ids' => $categoryIds,
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
      ->withStep($trigger)
      ->withStep($action)
      ->create();
  }

  private function createProductCategory(string $name): int {
    $term = wp_insert_term($name, 'product_cat');
    if (is_wp_error($term)) {
      throw new \RuntimeException("Could not create term: " . $term->get_error_message());
    }
    return (int)$term['term_id'];
  }

  private function createProduct(string $name, int $category, float $price = 1.99): int {

    $product = new \WC_Product();
    $product->set_name($name);
    $product->set_category_ids([$category]);
    $product->set_price((string)$price);
    $product->save();
    $this->assertTrue(in_array($category, $product->get_category_ids()));
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
