<?php

namespace MailPoet\Newsletter\Renderer;

use MailPoet\Newsletter\Editor\LayoutHelper;
use MailPoet\Newsletter\Renderer\Blocks\Renderer as BlocksRenderer;

class Preprocessor {
  const WC_HEADING_PLACEHOLDER = '[mailpet_woocommerce_heading_placeholder]';
  const WC_CONTENT_PLACEHOLDER = '[mailpet_woocommerce_content_placeholder]';

  /** @var BlocksRenderer */
  private $blocks_renderer;

  public function __construct(BlocksRenderer $blocks_renderer) {
    $this->blocks_renderer = $blocks_renderer;
  }

  /**
   * @param array $content
   * @return array
   */
  public function process($content) {
    if (!array_key_exists('blocks', $content)) {
      return $content;
    }
    $blocks = [];
    foreach ($content['blocks'] as $block) {
      $blocks = array_merge($blocks, $this->processBlock($block));
    }
    $content['blocks'] = $blocks;
    return $content;
  }

    /**
   * @param array $block
   * @return array
   */
  public function processBlock($block) {
    switch ($block['type']) {
      case 'automatedLatestContentLayout':
        return $this->blocks_renderer->automatedLatestContentTransformedPosts($block);
      case 'woocommerceHeading':
        return $this->placeholder(self::WC_HEADING_PLACEHOLDER);
      case 'woocommerceContent':
        return $this->placeholder(self::WC_CONTENT_PLACEHOLDER);
    }
    return [$block];
  }

  /**
   * @param array $block
   * @return array
   */
  private function placeholder($text) {
    return [
      LayoutHelper::row([
        LayoutHelper::col([[
          'type' => 'text',
          'text' => $text,
        ]]),
      ]),
    ];
  }
}