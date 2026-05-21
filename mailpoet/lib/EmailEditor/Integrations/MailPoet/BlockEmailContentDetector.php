<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\WP\Functions as WPFunctions;

class BlockEmailContentDetector {
  private const DYNAMIC_RENDERABLE_BLOCKS = [
    'woocommerce/coupon-code' => true,
    'woocommerce/product-collection' => true,
    'woocommerce/product-image' => true,
    'woocommerce/product-price' => true,
    'woocommerce/product-button' => true,
  ];

  private const MEDIA_OR_LINK_BLOCKS = [
    'core/audio' => true,
    'core/button' => true,
    'core/cover' => true,
    'core/file' => true,
    'core/gallery' => true,
    'core/image' => true,
    'core/media-text' => true,
    'core/video' => true,
  ];

  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  /** @param \WP_Post|string $postOrContent */
  public function hasMeaningfulContent($postOrContent): bool {
    if ($postOrContent instanceof \WP_Post) {
      $content = (string)$postOrContent->post_content; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    } elseif (is_string($postOrContent)) {
      $content = $postOrContent;
    } else {
      return false;
    }

    if (trim($content) === '') {
      return false;
    }

    if ($this->contentContainsPersonalizationToken($content)) {
      return true;
    }

    $blocks = $this->wp->parseBlocks($content);
    if ($this->blocksHaveMeaningfulContent($blocks)) {
      return true;
    }

    return $this->htmlHasVisibleText($content);
  }

  private function blocksHaveMeaningfulContent(array $blocks, bool $insideProductTemplate = false): bool {
    foreach ($blocks as $block) {
      if (!is_array($block)) {
        continue;
      }

      if ($this->blockHasMeaningfulContent($block, $insideProductTemplate)) {
        return true;
      }
    }
    return false;
  }

  private function blockHasMeaningfulContent(array $block, bool $insideProductTemplate): bool {
    $blockName = isset($block['blockName']) && is_string($block['blockName']) ? $block['blockName'] : null;
    $attrs = $this->getBlockAttrs($block);

    if ($this->isKnownRenderableDynamicBlock($blockName, $attrs, $insideProductTemplate)) {
      return true;
    }

    if ($this->blockContainsRenderableMediaOrLink($blockName, $block, $attrs)) {
      return true;
    }

    $blockContent = $this->getBlockContent($block);
    if ($this->contentContainsPersonalizationToken($blockContent) || $this->htmlHasVisibleText($blockContent)) {
      return true;
    }

    $innerBlocks = isset($block['innerBlocks']) && is_array($block['innerBlocks']) ? $block['innerBlocks'] : [];
    return $this->blocksHaveMeaningfulContent($innerBlocks, $insideProductTemplate || $blockName === 'woocommerce/product-template');
  }

  private function isKnownRenderableDynamicBlock(?string $blockName, array $attrs, bool $insideProductTemplate): bool {
    if ($blockName === null) {
      return false;
    }

    if (isset(self::DYNAMIC_RENDERABLE_BLOCKS[$blockName])) {
      return true;
    }

    if (!in_array($blockName, ['core/post-title', 'core/post-content'], true)) {
      return false;
    }

    $woocommerceNamespace = isset($attrs['__woocommerceNamespace']) && is_string($attrs['__woocommerceNamespace'])
      ? $attrs['__woocommerceNamespace']
      : '';
    return $insideProductTemplate || strpos($woocommerceNamespace, 'woocommerce/product-collection') === 0;
  }

  private function blockContainsRenderableMediaOrLink(?string $blockName, array $block, array $attrs): bool {
    if ($blockName !== null && isset(self::MEDIA_OR_LINK_BLOCKS[$blockName]) && $this->attrsContainRenderableValue($attrs)) {
      return true;
    }

    $content = strtolower($this->getBlockContent($block));
    foreach (['<img', '<picture', '<source', '<video', '<audio'] as $tag) {
      if (strpos($content, $tag) !== false) {
        return true;
      }
    }

    return strpos($content, '<a ') !== false && strpos($content, 'href=') !== false;
  }

  private function attrsContainRenderableValue(array $attrs): bool {
    foreach (['id', 'url', 'href', 'src', 'mediaId', 'mediaUrl'] as $key) {
      if (!empty($attrs[$key])) {
        return true;
      }
    }

    foreach ($attrs as $value) {
      if (is_string($value) && $this->contentContainsPersonalizationToken($value)) {
        return true;
      }
      if (is_array($value) && $this->attrsContainRenderableValue($value)) {
        return true;
      }
    }
    return false;
  }

  private function htmlHasVisibleText(string $content): bool {
    // Remove HTML comments so Gutenberg delimiters do not count as visible content.
    $contentWithoutComments = preg_replace('/<!--.*?-->/s', '', $content);
    if (!is_string($contentWithoutComments)) {
      $contentWithoutComments = $content;
    }

    return trim($this->wp->wpStripAllTags($contentWithoutComments, true)) !== '';
  }

  private function contentContainsPersonalizationToken(string $content): bool {
    return strpos($content, '[mailpoet/') !== false || strpos($content, '[woocommerce/') !== false;
  }

  private function getBlockAttrs(array $block): array {
    return isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : [];
  }

  private function getBlockContent(array $block): string {
    $content = isset($block['innerHTML']) && is_string($block['innerHTML']) ? $block['innerHTML'] : '';
    $innerContent = isset($block['innerContent']) && is_array($block['innerContent']) ? $block['innerContent'] : [];
    foreach ($innerContent as $contentPart) {
      if (is_string($contentPart)) {
        $content .= $contentPart;
      }
    }
    return $content;
  }
}
