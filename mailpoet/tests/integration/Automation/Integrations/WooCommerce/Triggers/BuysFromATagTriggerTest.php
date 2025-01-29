<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\BuysFromATagTrigger;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;

/**
 * @group woo
 */
class BuysFromATagTriggerTest extends \MailPoetTest {

  /** @var BuysFromATagTrigger */
  private $testee;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  public function _before() {
    $this->testee = $this->diContainer->get(BuysFromATagTrigger::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
  }

  public function testItDoesRunOnlyOncePerOrder() {

    $tag1 = $this->createProductTag("testItDoesRunOnlyOncePerOrder Tag 1");
    $tag2 = $this->createProductTag("testItDoesRunOnlyOncePerOrder Tag 2");
    $product1 = $this->createProduct('product 1', $tag1);
    $product2 = $this->createProduct('product 2', $tag2);
    $automation = $this->createAutomation([$tag1], 'completed');
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

    $tag1 = $this->createProductTag("testItDoesRunOnAnyStatus Tag 1");
    $tag2 = $this->createProductTag("testItDoesRunOnAnyStatus Tag 2");
    $product1 = $this->createProduct('product 1', $tag1);
    $product2 = $this->createProduct('product 2', $tag2);
    $automation = $this->createAutomation([$tag1], 'any');
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $order = $this->createOrder([$product1, $product2]);

    $this->testee->registerHooks();
    $order->set_status('on-hold');
    $order->save();
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
  }

  public function testItDoesNotRunWhenTagsDoNotMatch() {

    $tag1 = $this->createProductTag("testItDoesNotRunWhenCategoriesDoNotMatch Tag 1");
    $tag2 = $this->createProductTag("testItDoesNotRunWhenCategoriesDoNotMatch Tag 2");
    $product1 = $this->createProduct('product 1', $tag1);
    $automation = $this->createAutomation([$tag2], 'completed');
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
      BuysFromATagTrigger::KEY,
      [
        'tag_ids' => $categoryIds,
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

  private function createProductTag(string $name): int {
    $term = wp_insert_term($name, 'product_tag');
    if (is_wp_error($term)) {
      throw new \RuntimeException("Could not create term: " . $term->get_error_message());
    }
    return (int)$term['term_id'];
  }

  private function createProduct(string $name, int $tag, float $price = 1.99): int {

    $product = new \WC_Product();
    $product->set_name($name);
    $product->set_tag_ids([$tag]);
    $product->set_price((string)$price);
    $product->save();
    $this->assertTrue(in_array($tag, $product->get_tag_ids()));
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
