<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\OrderReviewUrl;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

/**
 * @group woo
 */
class OrderReviewUrlTest extends \MailPoetTest {
  public function testItReturnsEmptyStringWithoutOrderContext(): void {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->expects($this->never())->method('wcSupportsOrderReviewUrl');
    $wooCommerceHelper->expects($this->never())->method('wcOrderHasActionableReviewItems');
    $wooCommerceHelper->expects($this->never())->method('wcGetReviewOrderUrl');

    $tag = new OrderReviewUrl($wooCommerceHelper);

    $this->assertSame('', $tag->getUrl([]));
  }

  public function testItReturnsEmptyStringWhenWooCommerceDoesNotSupportOrderReviewUrls(): void {
    $order = new \WC_Order();
    $order->set_id(123);
    $order->set_order_key('wc_order_abc');
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->expects($this->once())
      ->method('wcSupportsOrderReviewUrl')
      ->willReturn(false);
    $wooCommerceHelper->expects($this->never())->method('wcOrderHasActionableReviewItems');
    $wooCommerceHelper->expects($this->never())->method('wcGetReviewOrderUrl');

    $tag = new OrderReviewUrl($wooCommerceHelper);

    $this->assertSame('', $tag->getUrl(['order' => $order]));
  }

  public function testItReturnsEmptyStringForUnsavedOrderContext(): void {
    $order = new \WC_Order();
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->expects($this->never())->method('wcSupportsOrderReviewUrl');
    $wooCommerceHelper->expects($this->never())->method('wcOrderHasActionableReviewItems');
    $wooCommerceHelper->expects($this->never())->method('wcGetReviewOrderUrl');

    $tag = new OrderReviewUrl($wooCommerceHelper);

    $this->assertSame('', $tag->getUrl(['order' => $order]));
  }

  public function testItReturnsEmptyStringWhenOrderHasNoActionableReviewItems(): void {
    $order = new \WC_Order();
    $order->set_id(123);
    $order->set_order_key('wc_order_abc');
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->expects($this->once())
      ->method('wcSupportsOrderReviewUrl')
      ->willReturn(true);
    $wooCommerceHelper->expects($this->once())
      ->method('wcOrderHasActionableReviewItems')
      ->with($order)
      ->willReturn(false);
    $wooCommerceHelper->expects($this->never())->method('wcGetReviewOrderUrl');

    $tag = new OrderReviewUrl($wooCommerceHelper);

    $this->assertSame('', $tag->getUrl(['order' => $order]));
  }

  public function testItReturnsWooCommerceReviewOrderUrl(): void {
    $order = new \WC_Order();
    $order->set_id(123);
    $order->set_order_key('wc_order_abc');
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->expects($this->once())
      ->method('wcSupportsOrderReviewUrl')
      ->willReturn(true);
    $wooCommerceHelper->expects($this->once())
      ->method('wcOrderHasActionableReviewItems')
      ->with($order)
      ->willReturn(true);
    $wooCommerceHelper->expects($this->once())
      ->method('wcGetReviewOrderUrl')
      ->with($order)
      ->willReturn('https://example.com/review-order/123/?key=abc');

    $tag = new OrderReviewUrl($wooCommerceHelper);

    $this->assertSame('https://example.com/review-order/123/?key=abc', $tag->getUrl(['order' => $order]));
  }
}
