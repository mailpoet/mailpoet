<?php

namespace MailPoet\Form\Block;

class Text extends Base {

  public function render($block) {
    $type = 'text';
    $automationId = ' ';
    if ($block['id'] === 'email') {
      $type = 'email';
      $automationId = 'data-automation-id="form_email" ';
    }

    $html = '<p class="mailpoet_paragraph">';

    $html .= $this->renderLabel($block);

    $html .= '<input type="' . $type . '" class="mailpoet_text" ';

    $html .= 'name="data[' . $this->getFieldName($block) . ']" ';

    $html .= 'title="' . $this->getFieldLabel($block) . '" ';

    $html .= 'value="' . $this->getFieldValue($block) . '" ';

    $html .= $automationId;

    $html .= $this->renderInputPlaceholder($block);

    $html .= $this->getInputValidation($block);

    $html .= $this->getInputModifiers($block);

    $html .= '/>';

    $html .= '</p>';

    return $html;
  }
}
