<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form\Block;

use MailPoet\WP\Functions as WPFunctions;

class Html {
  /** @var BlockRendererHelper */
  private $rendererHelper;

  private WPFunctions $wp;

  public function __construct(
    BlockRendererHelper $rendererHelper,
    WPFunctions $wp
  ) {
    $this->rendererHelper = $rendererHelper;
    $this->wp = $wp;
  }

  public function render(array $block, array $formSettings): string {
    $html = '';
    $text = '';

    if (isset($block['params']['text']) && $block['params']['text']) {
      $text = html_entity_decode($block['params']['text'], ENT_QUOTES);
    }

    if (isset($block['params']['nl2br']) && $block['params']['nl2br']) {
      $text = nl2br($text);
    }

    $classes = isset($block['params']['class_name']) ? " " . $block['params']['class_name'] : '';
    $html .= '<div class="mailpoet_paragraph' . $this->wp->escAttr($classes) . '" ' . $this->rendererHelper->renderFontStyle($formSettings) . '>';
    $html .= $this->wp->wpKsesPost($text);
    $html .= '</div>';

    return $html;
  }
}
