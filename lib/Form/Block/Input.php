<?php
namespace MailPoet\Form\Block;

class Input extends Base {

  static function render($block) {
    $html = '';

    $html .= '<p class="mailpoet_paragraph">';

    $html .= static::renderLabel($block);

    $html .= '<input type="text" class="mailpoet_input" ';

    $html .= 'name="'.static::getFieldName($block).'" ';

    $html .= 'title="'.static::getFieldLabel($block).'" ';

    $html .= 'value="'.static::getFieldValue($block).'" ';

    $html .= static::renderInputPlaceholder($block);

    $html .= static::getInputValidation($block);

    $html .= '/>';

    $html .= '</p>';

    return $html;
  }
}