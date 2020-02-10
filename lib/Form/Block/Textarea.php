<?php

namespace MailPoet\Form\Block;

class Textarea {
  /** @var Base */
  private $baseRenderer;

  public function __construct(Base $baseRenderer) {
    $this->baseRenderer = $baseRenderer;
  }

  public function render($block) {
    $html = '';

    $html .= '<p class="mailpoet_paragraph">';

    $html .= $this->baseRenderer->renderLabel($block);

    $lines = (isset($block['params']['lines']) ? (int)$block['params']['lines'] : 1);

    $html .= '<textarea class="mailpoet_textarea" rows="' . $lines . '" ';

    $html .= 'name="data[' . $this->baseRenderer->getFieldName($block) . ']"';

    $html .= $this->baseRenderer->renderInputPlaceholder($block);

    $html .= $this->baseRenderer->getInputValidation($block);

    $html .= $this->baseRenderer->getInputModifiers($block);

    $html .= '>' . $this->baseRenderer->getFieldValue($block) . '</textarea>';

    $html .= '</p>';

    return $html;
  }
}
