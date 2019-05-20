<?php
namespace MailPoet\Form\Block;

use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;


class Select extends Base {

  static function render($block) {
    $html = '';

    $field_name = 'data[' . static::getFieldName($block) . ']';
    $field_validation = static::getInputValidation($block);
    $automation_id = ($block['id'] == 'status') ? 'data-automation-id="form_status"' : '';
    $html .= '<p class="mailpoet_paragraph">';
    $html .= static::renderLabel($block);
    $html .= '<select class="mailpoet_select" name="' . $field_name . '" ' . $automation_id . '>';

    if (isset($block['params']['label_within']) && $block['params']['label_within']) {
      $html .= '<option value="">' . static::getFieldLabel($block) . '</option>';
    } else {
      if (empty($block['params']['required']) || !$block['params']['required']) {
        $html .= '<option value="">-</option>';
      }
    }

    $options = (!empty($block['params']['values'])
      ? $block['params']['values']
      : []
    );

    foreach ($options as $option) {
      if (!empty($option['is_hidden'])) {
        continue;
      }

      $is_selected = (
        (isset($option['is_checked']) && $option['is_checked'])
        ||
        (self::getFieldValue($block) === $option['value'])
      ) ? ' selected="selected"' : '';

      $is_disabled = (!empty($option['is_disabled'])) ? ' disabled="disabled"' : '';

      if (is_array($option['value'])) {
        $value = key($option['value']);
        $label = reset($option['value']);
      } else {
        $value = $option['value'];
        $label = $option['value'];
      }

      $html .= '<option value="' . $value . '"' . $is_selected . $is_disabled . '>';
      $html .= WPFunctions::get()->escAttr($label);
      $html .= '</option>';
    }
    $html .= '</select>';

    $html .= '</p>';

    return $html;
  }
}
