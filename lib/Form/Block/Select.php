<?php
namespace MailPoet\Form\Block;

class Select extends Base {

  static function render($block) {
    $html = '';

    $field_name = static::getFieldName($block);
    $field_validation = static::getInputValidation($block);

    $html .= '<p class="mailpoet_paragraph">';
    $html .= static::renderLabel($block);
    $html .= '<select class="mailpoet_select" name="'.$field_name.'">';

    if(isset($block['params']['label_within'])
    && $block['params']['label_within']) {
      $html .= '<option value="">'.static::getFieldLabel($block).'</option>';
    }

    foreach($block['params']['values'] as $option) {
      $is_selected = (
        (isset($option['is_checked']) && $option['is_checked'])
        ||
        (self::getFieldValue($block) === $option['value'])
      ) ? 'selected="selected"' : '';

      if(is_array($option['value'])) {
        $value = key($option['value']);
        $label = reset($option['value']);
      } else {
        $value = $option['value'];
        $label = $option['value'];
      }

      $html .= '<option value="'.$value.'" '.$is_selected.'>';
      $html .= esc_attr($label);
      $html .= '</option>';
    }
    $html .= '</select>';

    $html .= '</p>';

    return $html;
  }
}