<?php
namespace MailPoet\Form\Block;

class Checkbox extends Base {

  static function render($block) {
    $html = '';

    // TODO: check if it still makes sense
    // create hidden default value
    // $html .= '<input type="hidden"name="'.$field_name.'" value="0" '.static::getInputValidation($block).'/>';

    $html .= '<p class="mailpoet_paragraph">';

    $html .= static::renderLabel($block);

    foreach($block['params']['values'] as $option) {
      $html .= '<label class="mailpoet_checkbox_label">';

      $html .= '<input type="checkbox" class="mailpoet_checkbox" ';

      $html .= 'name="'.static::getFieldName($block).'" ';

      $html .= 'value="1" ';

      $html .= (isset($option['is_checked']) && $option['is_checked'])
                ? 'checked="checked"' : '';
      $html .= static::getInputValidation($block);

      $html .= ' />'.$option['value'];

      $html .= '</label>';
    }

    $html .= '</p>';

    return $html;
  }
}

