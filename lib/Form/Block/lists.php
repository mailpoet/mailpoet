<?php
namespace MailPoet\Form\Block;

class Lists extends Base {

  static function render($block) {
    $html = '';

    $field_name = static::getFieldName($block);
    $field_validation = static::getInputValidation($block);

    $html .= '<p class="mailpoet_paragraph">';

    $html .= static::renderLabel($block);

    if(!empty($block['params']['values'])) {
      // display values
      foreach($block['params']['values'] as $list) {
        if(!isset($list['id']) || !isset($list['name'])) continue;

        $is_checked = (isset($list['is_checked']) && $list['is_checked']) ? 'checked="checked"' : '';

        $html .= '<label class="mailpoet_checkbox_label">';
        $html .= '<input type="checkbox" class="mailpoet_checkbox" ';
        $html .= 'name="'.$field_name.'" ';
        $html .= 'value="'.$list['id'].'" '.$is_checked;
        $html .= $field_validation;
        $html .= ' />'.$list['name'];
        $html .= '</label>';
      }
    }

    $html .= '</p>';

    return $html;
  }
}