<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet;

use MailPoet\Config\AccessControl;
use MailPoet\EmailEditor\Integrations\MailPoet\AutomationEmailPreviewOrderProvider;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

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

    $provider = new AutomationEmailPreviewOrderProvider($woocommerceHelper, $this->createWp(true));

    $this->assertSame($order, $provider->getOrder());
  }

  public function testItReturnsNullWhenNoExistingReviewableOrderIsAvailable(): void {
    $woocommerceHelper = $this->createMock(WooCommerceHelper::class);
    $woocommerceHelper->expects($this->once())
      ->method('wcGetOrders')
      ->willReturn([]);

    $provider = new AutomationEmailPreviewOrderProvider($woocommerceHelper, $this->createWp(true));

    $this->assertNull($provider->getOrder());
  }

  public function testItReturnsNullWithoutEmailPermission(): void {
    $woocommerceHelper = $this->createMock(WooCommerceHelper::class);
    $woocommerceHelper->expects($this->never())
      ->method('wcGetOrders');

    $provider = new AutomationEmailPreviewOrderProvider($woocommerceHelper, $this->createWp(false));

    $this->assertNull($provider->getOrder());
  }

  private function createWp(bool $canManageEmails): WPFunctions {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('currentUserCan')
      ->willReturnCallback(function ($capability) use ($canManageEmails): bool {
        return $capability === AccessControl::PERMISSION_MANAGE_EMAILS && $canManageEmails;
      });
    return $wp;
  }
}
