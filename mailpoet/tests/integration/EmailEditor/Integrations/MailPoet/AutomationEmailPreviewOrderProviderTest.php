<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet;

use MailPoet\EmailEditor\Integrations\MailPoet\AutomationEmailPreviewOrderProvider;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

/**
 * @group woo
 */
class AutomationEmailPreviewOrderProviderTest extends \MailPoetTest {
  public function testItPrefersExistingReviewableCompletedOrder(): void {
    $product = $this->tester->createWooCommerceProduct([
      'name' => 'Reviewable product',
      'price' => 20,
      'status' => 'publish',
    ]);
    $product->set_reviews_allowed(true);
    $product->save();

    $order = $this->tester->createWooCommerceOrder();
    $order->add_product($product, 1);
    $order->calculate_totals();
    $order->save();

    $woocommerceHelper = $this->createMock(WooCommerceHelper::class);
    $woocommerceHelper->expects($this->once())
      ->method('wcGetOrders')
      ->with($this->callback(function(array $args): bool {
        return ($args['status'] ?? null) === 'completed'
          && ($args['limit'] ?? null) === 10
          && ($args['order'] ?? null) === 'DESC';
      }))
      ->willReturn([$order]);
    $woocommerceHelper->expects($this->once())
      ->method('wcOrderHasActionableReviewItems')
      ->with($order)
      ->willReturn(true);

    $provider = new AutomationEmailPreviewOrderProvider($woocommerceHelper);

    $this->assertSame($order, $provider->getOrder());
  }

  public function testItReturnsNullWhenNoExistingReviewableOrderIsAvailable(): void {
    $woocommerceHelper = $this->createMock(WooCommerceHelper::class);
    $woocommerceHelper->expects($this->once())
      ->method('wcGetOrders')
      ->willReturn([]);

    $provider = new AutomationEmailPreviewOrderProvider($woocommerceHelper);

    $this->assertNull($provider->getOrder());
  }
}
