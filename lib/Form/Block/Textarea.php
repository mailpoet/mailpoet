<?php

namespace MailPoet\Form\Block;

use MailPoet\Form\TextInputStylesRenderer;

class Textarea {
  /** @var BlockRendererHelper */
  private $rendererHelper;

  /** @var TextInputStylesRenderer */
  private $inputStylesRenderer;

  public function __construct(BlockRendererHelper $rendererHelper, TextInputStylesRenderer $inputStylesRenderer) {
    $this->rendererHelper = $rendererHelper;
    $this->inputStylesRenderer = $inputStylesRenderer;
  }

  public function render(array $block, array $formSettings): string {
    $html = '';
    $styles = $this->inputStylesRenderer->render($block['styles'] ?? []);

    $html .= '<div class="mailpoet_paragraph">';

    $html .= $this->rendererHelper->renderLabel($block, $formSettings);

    $lines = (isset($block['params']['lines']) ? (int)$block['params']['lines'] : 1);

    $html .= '<textarea class="mailpoet_textarea" rows="' . $lines . '" ';

    $html .= 'name="data[' . $this->rendererHelper->getFieldName($block) . ']"';

    $html .= $this->rendererHelper->renderInputPlaceholder($block);

    $html .= $this->rendererHelper->getInputValidation($block);

    $html .= $this->rendererHelper->getInputModifiers($block);

    if ($styles) {
      $html .= 'style="' . $styles . '" ';
    }

    $html .= '>' . $this->rendererHelper->getFieldValue($block) . '</textarea>';

    $html .= '</div>';

    return $html;
  }
}
