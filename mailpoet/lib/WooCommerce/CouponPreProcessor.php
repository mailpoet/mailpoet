<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\Blocks\Coupon;

class CouponPreProcessor {

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function __construct(
    NewslettersRepository $newslettersRepository
  ) {
    $this->newslettersRepository = $newslettersRepository;
  }

  public function processCoupons(NewsletterEntity $newsletter, array $blocks, bool $preview = false): array {
    if ($preview) {
      return $blocks;
    }

    $generated = $this->ensureCouponForBlocks($blocks);
    $body = $newsletter->getBody();

    if ($generated && $body) {
      $updatedBody = array_merge(
        $body,
        [
          'content' => array_merge(
            $body['content'],
            ['blocks' => $blocks]
          ),
        ]
      );
      $newsletter->setBody($updatedBody);
      $this->newslettersRepository->flush();
    }
    return $blocks;
  }

  private function ensureCouponForBlocks(array &$blocks): bool {

    static $generated = false;
    foreach ($blocks as &$innerBlock) {
      if (isset($innerBlock['blocks']) && !empty($innerBlock['blocks'])) {
        $this->ensureCouponForBlocks($innerBlock['blocks']);
      }
      if (isset($innerBlock['type']) && $innerBlock['type'] === Coupon::TYPE && $this->shouldGenerateCoupon($innerBlock)) {
        $innerBlock['couponId'] = $this->generateCoupon();
        $generated = true;
      }
    }

    return $generated;
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

  private function shouldGenerateCoupon(array $block): bool {
    return empty($block['couponId']);
  }
}
