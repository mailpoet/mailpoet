<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Renderer\Blocks\Coupon;

class CouponPreProcessor {
  public function processCoupons(NewsletterEntity $newsletter, array $block, bool $preview = false): array {
    if ($preview) {
      return $block;
    }

    $couponBlocks = $this->findCouponBlocks($block);
    foreach ($couponBlocks as $couponBlock) {
      if (empty($couponBlock['couponId'])) {
        $couponBlock['couponId'] = $this->generateCoupon();
      }
    }

    return $block;
  }

  private function findCouponBlocks(array $block): array {
    $coupons = [];

    foreach ($block['blocks'] as $innerBlock) {
      if (isset($innerBlock['blocks']) && !empty($innerBlock['blocks'])) {
        $coupons = array_merge($coupons, $this->findCouponBlocks($innerBlock));
      }
      if ($innerBlock['type'] === Coupon::TYPE) {
        $coupons[] = $innerBlock;
      }
    }

    return $coupons;
  }

  private function generateCoupon(string $couponCode = null): int {
    $coupon = new \WC_Coupon();
    $code = $couponCode ?? $this->generateRandomCode();
    $coupon->set_code($code);

    return $coupon->save();
  }

  /**
   * Generates Coupon code for XXXX-XXXXXX-XXXX pattern
   */
  private function generateRandomCode(): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $length = strlen($chars);
    return sprintf(
      "%s-%s-%s",
      substr($chars, rand(0, $length - 5), 4),
      substr($chars, rand(0, $length - 8), 7),
      substr($chars, rand(0, $length - 5), 4)
    );
  }
}
