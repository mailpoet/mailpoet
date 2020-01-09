<?php

namespace MailPoet\Form\Block;

class Text extends Base {

  public static function render($block) {
    $type = 'text';
    $automationId = ' ';
    if ($block['id'] === 'email') {
      $type = 'email';
      $automationId = 'data-automation-id="form_email" ';
    }

    $html = '<p class="mailpoet_paragraph">';

    $html .= static::renderLabel($block);

    $html .= '<input type="' . $type . '" class="mailpoet_text" ';

    $html .= 'name="data[' . static::getFieldName($block) . ']" ';

    $html .= 'title="' . static::getFieldLabel($block) . '" ';

    $html .= 'value="' . static::getFieldValue($block) . '" ';

    $html .= $automationId;

    $html .= static::renderInputPlaceholder($block);

    $html .= static::getInputValidation($block);

    $html .= static::getInputModifiers($block);

    $html .= '/>';

    $html .= '</p>';

    return $html;
  }
}
