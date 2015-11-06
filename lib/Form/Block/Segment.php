<?php
namespace MailPoet\Form\Block;

class Segment extends Base {

  static function render($block) {
    $html = '';

    $field_name = static::getFieldName($block);
    $field_validation = static::getInputValidation($block);

    $html .= '<p class="mailpoet_paragraph">';

    $html .= static::renderLabel($block);

    if(!empty($block['params']['values'])) {
      // display values
      foreach($block['params']['values'] as $segment) {
        if(!isset($segment['id']) || !isset($segment['name'])) continue;

        $is_checked = (isset($segment['is_checked']) && $segment['is_checked']) ? 'checked="checked"' : '';

        $html .= '<label class="mailpoet_checkbox_label">';
        $html .= '<input type="checkbox" class="mailpoet_checkbox" ';
        $html .= 'name="'.$field_name.'[]" ';
        $html .= 'value="'.$segment['id'].'" '.$is_checked.' ';
        $html .= $field_validation;
        $html .= ' />'.$segment['name'];
        $html .= '</label>';
      }
    }

    $html .= '</p>';

    return $html;
  }
}