<?php
if(!defined('ABSPATH')) exit;

namespace MailPoet\Form\Block;

class Text extends Base {

  static function render($block) {
    $type = 'text';
    if($block['id'] === 'email') {
      $type = 'email';
    }

    $html = '';

    $html .= '<p class="mailpoet_paragraph">';

    $html .= static::renderLabel($block);

    $html .= '<input type="'.$type.'" class="mailpoet_text" ';

    $html .= 'name="'.static::getFieldName($block).'" ';

    $html .= 'title="'.static::getFieldLabel($block).'" ';

    $html .= 'value="'.static::getFieldValue($block).'" ';

    $html .= static::renderInputPlaceholder($block);

    $html .= static::getInputValidation($block);

    $html .= static::getInputModifiers($block);

    $html .= '/>';

    $html .= '</p>';

    return $html;
  }
}