<?php

namespace MailPoet\Form\Block;

class Html {
  /** @var BlockRendererHelper */
  private $rendererHelper;

  public function __construct(BlockRendererHelper $rendererHelper) {
    $this->rendererHelper = $rendererHelper;
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

    $html .= '<p class="mailpoet_paragraph" ' . $this->rendererHelper->renderFontStyle($formSettings) . '>';
    $html .= $text;
    $html .= '</p>';

    return $html;
  }
}
