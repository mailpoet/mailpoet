<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Preview;

/**
 * @group woo
 */
class WooCommerceDummyDataTest extends \MailPoetTest {
  /** @var WooCommerceDummyData */
  private $dummyData;

  public function _before() {
    parent::_before();
    if (!class_exists(\WC_Order::class)) {
      $this->markTestSkipped('WooCommerce is not active.');
    }
    $this->dummyData = $this->diContainer->get(WooCommerceDummyData::class);
  }

  public function testDummyOrderSaveDoesNotPersistToDatabase(): void {
    $order = $this->dummyData->getOrder();
    $this->assertInstanceOf(\WC_Order::class, $order);

    // The id stays 0 so the order can never touch a real row; the display
    // number is surfaced through get_order_number() instead.
    $this->assertSame(0, $order->get_id());
    $this->assertSame('12345', $order->get_order_number());

    // save() must be a no-op: it returns the id (0) and creates no order.
    $ordersBefore = wc_get_orders(['limit' => -1, 'return' => 'ids']);
    $this->assertSame(0, $order->save());
    $this->assertSame($ordersBefore, wc_get_orders(['limit' => -1, 'return' => 'ids']));
  }

  public function testDummyOrderSaveCannotOverwriteARealOrder(): void {
    $realOrder = $this->tester->createWooCommerceOrder(['billing_email' => 'real-customer@example.com']);
    $realOrderId = $realOrder->get_id();

    // Simulate a stray preview hook that points the dummy order at a real order
    // ID and then persists it. Before the fix this overwrote the real order.
    $dummyOrder = $this->dummyData->getOrder();
    $this->assertInstanceOf(\WC_Order::class, $dummyOrder);
    $dummyOrder->set_id($realOrderId);
    $dummyOrder->set_billing_email('john@company.com');
    $dummyOrder->set_billing_first_name('John');
    $dummyOrder->save();
    $dummyOrder->save_meta_data();

    $reloaded = wc_get_order($realOrderId);
    $this->assertInstanceOf(\WC_Order::class, $reloaded);
    $this->assertSame('real-customer@example.com', $reloaded->get_billing_email());
  }

  public function testDummyOrderDeleteCannotRemoveARealOrder(): void {
    $realOrder = $this->tester->createWooCommerceOrder(['billing_email' => 'keep-me@example.com']);
    $realOrderId = $realOrder->get_id();

    $dummyOrder = $this->dummyData->getOrder();
    $this->assertInstanceOf(\WC_Order::class, $dummyOrder);
    $dummyOrder->set_id($realOrderId);
    $this->assertFalse($dummyOrder->delete(true));

    $this->assertInstanceOf(\WC_Order::class, wc_get_order($realOrderId));
  }

  public function testDummyOrderDoesNotLeakRealOrderMeta(): void {
    $realOrder = $this->tester->createWooCommerceOrder();
    $realOrder->update_meta_data('_secret_preview_leak', 'sensitive-value');
    $realOrder->save();
    $realOrderId = $realOrder->get_id();

    $dummyOrder = $this->dummyData->getOrder();
    $this->assertInstanceOf(\WC_Order::class, $dummyOrder);
    $dummyOrder->set_id($realOrderId);

    // read_meta_data() is neutralised, so meta is never lazy-loaded from the DB.
    $this->assertSame('', $dummyOrder->get_meta('_secret_preview_leak'));
  }
}
