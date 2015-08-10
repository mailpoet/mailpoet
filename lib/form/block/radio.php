<?php
namespace MailPoet\Form\Block;

class Radio extends Base {

  static function render($block) {
    $html = '';

    // TODO: check if it still makes sense
    // create hidden default value
    // $html .= '<input type="hidden"name="'.$field_name.'" value="0" '.static::getInputValidation($block).'/>';

    $html .= '<p class="mailpoet_paragraph">';

    $html .= static::renderLabel($block);

    foreach($block['params']['values'] as $option) {
      $html .= '<label class="mailpoet_radio_label">';

      $html .= '<input type="radio" class="mailpoet_radio" ';

      $html .= 'name="'.static::getFieldName($block).'" ';

      $html .= 'value="'.$option['value'].'" ';

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