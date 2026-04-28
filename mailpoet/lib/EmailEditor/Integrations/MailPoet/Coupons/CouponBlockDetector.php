<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Coupons;

use MailPoet\WP\Functions as WPFunctions;

class CouponBlockDetector {
  const BLOCK_NAME = CouponBlock::NAME;
  const SAFE_PLACEHOLDER = CouponBlock::SAFE_PLACEHOLDER;

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

  public function hasRecipientRestrictedCreateNewCouponBlock(string $postContent): bool {
    return $this->blocksContainRecipientRestrictedCreateNewCoupon($this->wp->parseBlocks($postContent));
  }

  private function blocksContainCreateNewCoupon(array $blocks): bool {
    foreach ($blocks as $block) {
      if (!is_array($block)) {
        continue;
      }

      $blockName = $block['blockName'] ?? null;
      if ($blockName === self::BLOCK_NAME) {
        if ($this->isCreateNewCouponBlock($block)) {
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

  private function blocksContainRecipientRestrictedCreateNewCoupon(array $blocks): bool {
    foreach ($blocks as $block) {
      if (!is_array($block)) {
        continue;
      }

      $blockName = $block['blockName'] ?? null;
      if ($blockName === self::BLOCK_NAME) {
        $attrs = $this->getBlockAttrs($block);
        if ($this->isCreateNewCouponBlock($block) && !empty($attrs['restrictToSubscriber'])) {
          return true;
        }
      }

      $innerBlocks = isset($block['innerBlocks']) && is_array($block['innerBlocks']) ? $block['innerBlocks'] : [];
      if ($innerBlocks && $this->blocksContainRecipientRestrictedCreateNewCoupon($innerBlocks)) {
        return true;
      }
    }

    return false;
  }

  private function isCreateNewCouponBlock(array $block): bool {
    return CouponBlock::isCreateNew(
      $this->getBlockAttrs($block),
      $this->blockContainsGeneratedCouponPlaceholder($block)
    );
  }

  private function getBlockAttrs(array $block): array {
    return isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : [];
  }

  private function blockContainsGeneratedCouponPlaceholder(array $block): bool {
    if (isset($block['innerHTML']) && is_string($block['innerHTML']) && strpos($block['innerHTML'], self::SAFE_PLACEHOLDER) !== false) {
      return true;
    }

    $innerContent = isset($block['innerContent']) && is_array($block['innerContent']) ? $block['innerContent'] : [];
    foreach ($innerContent as $contentPart) {
      if (is_string($contentPart) && strpos($contentPart, self::SAFE_PLACEHOLDER) !== false) {
        return true;
      }
    }

    return false;
  }
}
