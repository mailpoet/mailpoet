<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\WP\Functions as WPFunctions;

class GutenbergCouponBlockDetector {
  const BLOCK_NAME = 'woocommerce/coupon-code';

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function hasCreateNewCouponBlock(string $postContent): bool {
    return $this->blocksContainCreateNewCoupon($this->wp->parseBlocks($postContent));
  }

  private function blocksContainCreateNewCoupon(array $blocks): bool {
    foreach ($blocks as $block) {
      if (!is_array($block)) {
        continue;
      }

      $blockName = $block['blockName'] ?? null;
      if ($blockName === self::BLOCK_NAME) {
        $attrs = isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : [];
        if (($attrs['source'] ?? 'createNew') === 'createNew') {
          return true;
        }
      }

      $innerBlocks = isset($block['innerBlocks']) && is_array($block['innerBlocks']) ? $block['innerBlocks'] : [];
      if ($innerBlocks && $this->blocksContainCreateNewCoupon($innerBlocks)) {
        return true;
      }
    }

    return false;
  }
}
