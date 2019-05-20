<?php
namespace MailPoet\Form\Block;

if (!defined('ABSPATH')) exit;

class Radio extends Base {

  static function render($block) {
    $html = '';

    $field_name = 'data[' . static::getFieldName($block) . ']';
    $field_validation = static::getInputValidation($block);

    $html .= '<p class="mailpoet_paragraph">';

    $html .= static::renderLabel($block);

    $options = (!empty($block['params']['values'])
      ? $block['params']['values']
      : []
    );

    $selected_value = self::getFieldValue($block);

    foreach ($options as $option) {
      $html .= '<label class="mailpoet_radio_label">';

      $html .= '<input type="radio" class="mailpoet_radio" ';

      $html .= 'name="' . $field_name . '" ';

      if (is_array($option['value'])) {
        $value = key($option['value']);
        $label = reset($option['value']);
      } else {
        $value = $option['value'];
        $label = $option['value'];
      }

      $html .= 'value="' . esc_attr($value) . '" ';

      $html .= (
        (
          $selected_value === ''
          && isset($option['is_checked'])
          && $option['is_checked']
        ) || ($selected_value === $value)
      ) ? 'checked="checked"' : '';

      $html .= $field_validation;
      $html .= ' /> ' . esc_attr($label);
      $html .= '</label>';
    }

    $html .= '<span class="mailpoet_error_' . $block['id'] . '"></span>';

    $html .= '</p>';

    return $html;
  }
}
