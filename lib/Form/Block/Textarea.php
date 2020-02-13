<?php

namespace MailPoet\Form\Block;

class Textarea {
  /** @var BlockRendererHelper */
  private $rendererHelper;

  public function __construct(BlockRendererHelper $rendererHelper) {
    $this->rendererHelper = $rendererHelper;
  }

  public function render(array $block): string {
    $html = '';

    $html .= '<p class="mailpoet_paragraph">';

    $html .= $this->rendererHelper->renderLabel($block);

    $lines = (isset($block['params']['lines']) ? (int)$block['params']['lines'] : 1);

    $html .= '<textarea class="mailpoet_textarea" rows="' . $lines . '" ';

    $html .= 'name="data[' . $this->rendererHelper->getFieldName($block) . ']"';

    $html .= $this->rendererHelper->renderInputPlaceholder($block);

    $html .= $this->rendererHelper->getInputValidation($block);

    $html .= $this->rendererHelper->getInputModifiers($block);

    $html .= '>' . $this->rendererHelper->getFieldValue($block) . '</textarea>';

    $html .= '</p>';

    return $html;
  }
}
