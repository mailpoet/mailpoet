<?php
namespace MailPoet\Form\Block;

class Checkbox extends Base {

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
      $html .= '<label class="mailpoet_checkbox_label">';
      $html .= '<input type="hidden" name="'.$field_name.'" value="" />';
      $html .= '<input type="checkbox" class="mailpoet_checkbox" ';

      $html .= 'name="'.$field_name.'" ';

      $html .= 'value="1" ';

      $html .= (
        (isset($option['is_checked']) && $option['is_checked'])
        ||
        (self::getFieldValue($block))
      ) ? 'checked="checked"' : '';

      $html .= $field_validation;

      $html .= ' /> '.esc_attr($option['value']);

      $html .= '</label>';
    }

    $html .= '<span class="mailpoet_error_'.$block['id'].'"></span>';

    $html .= '</p>';

    return $html;
  }
}

