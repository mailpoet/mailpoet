<?php

namespace MailPoet\Form\Block;

class Textarea extends Base {
  public function render($block) {
    $html = '';

    $html .= '<p class="mailpoet_paragraph">';

    $html .= $this->renderLabel($block);

    $lines = (isset($block['params']['lines']) ? (int)$block['params']['lines'] : 1);

    $html .= '<textarea class="mailpoet_textarea" rows="' . $lines . '" ';

    $html .= 'name="data[' . $this->getFieldName($block) . ']"';

    $html .= $this->renderInputPlaceholder($block);

    $html .= $this->getInputValidation($block);

    $html .= $this->getInputModifiers($block);

    $html .= '>' . $this->getFieldValue($block) . '</textarea>';

    $html .= '</p>';

    return $html;
  }
}
