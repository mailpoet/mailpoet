<?php

namespace MailPoet\Newsletter\Renderer;

use MailPoet\Newsletter\Editor\LayoutHelper;
use MailPoet\Newsletter\Renderer\Blocks\Renderer as BlocksRenderer;
use MailPoet\WP\Functions as WPFunctions;

class Preprocessor {
  const WC_HEADING_PLACEHOLDER = '[mailpet_woocommerce_heading_placeholder]';
  const WC_CONTENT_PLACEHOLDER = '[mailpet_woocommerce_content_placeholder]';

  /** @var BlocksRenderer */
  private $blocks_renderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(BlocksRenderer $blocks_renderer, WPFunctions $wp) {
    $this->blocks_renderer = $blocks_renderer;
    $this->wp = $wp;
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
        $base_color = $this->wp->getOption('woocommerce_email_base_color');
        $text_color = $this->wp->getOption('woocommerce_email_text_color');
        $content = '<h1 style="color:' . $text_color . ';">' . self::WC_HEADING_PLACEHOLDER . '</h1>';
        return $this->placeholder($content, ['backgroundColor' => $base_color]);
      case 'woocommerceContent':
        return $this->placeholder(self::WC_CONTENT_PLACEHOLDER);
    }
    return [$block];
  }

  /**
   * @param string $text
   * @return array
   */
  private function placeholder($text, $styles = []) {
    return [
      LayoutHelper::row([
        LayoutHelper::col([[
          'type' => 'text',
          'text' => $text,
        ]]),
        ], $styles),
    ];
  }
}