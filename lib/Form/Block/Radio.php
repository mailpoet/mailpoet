<?php
namespace MailPoet\Form\Block;

class Radio extends Base {

  static function render($block) {
    $html = '';

    $field_name = static::getFieldName($block);
    $field_validation = static::getInputValidation($block);

    $html .= '<p class="mailpoet_paragraph">';

    $html .= static::renderLabel($block);

    $options = (!empty($block['params']['values'])
      ? $block['params']['values']
      : array()
    );

    foreach($options as $option) {
      $html .= '<label class="mailpoet_radio_label">';

      $html .= '<input type="radio" class="mailpoet_radio" ';

      $html .= 'name="'.$field_name.'" ';

      $html .= 'value="'.esc_attr($option['value']).'" ';

      $html .= (isset($option['is_checked']) && $option['is_checked'])
                ? 'checked="checked"' : '';
      $html .= $field_validation;

      $html .= ' />&nbsp;'.esc_attr($option['value']);

      $html .= '</label>';
    }

    $html .= '<span class="mailpoet_error_'.$block['id'].'"></span>';

    $html .= '</p>';

    return $html;
  }
}