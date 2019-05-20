<?php
namespace MailPoet\Form\Block;

if (!defined('ABSPATH')) exit;

class Segment extends Base {

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

    foreach ($options as $option) {
      if (!isset($option['id']) || !isset($option['name'])) continue;

      $is_checked = (isset($option['is_checked']) && $option['is_checked']) ? 'checked="checked"' : '';

      $html .= '<label class="mailpoet_checkbox_label">';
      $html .= '<input type="checkbox" class="mailpoet_checkbox" ';
      $html .= 'name="' . $field_name . '[]" ';
      $html .= 'value="' . $option['id'] . '" ' . $is_checked . ' ';
      $html .= $field_validation;
      $html .= ' /> ' . esc_attr($option['name']);
      $html .= '</label>';
    }

    $html .= '<span class="mailpoet_error_' . $block['id'] . '"></span>';

    $html .= '</p>';

    return $html;
  }
}
