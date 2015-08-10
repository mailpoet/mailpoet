<?php
namespace MailPoet\Form\Block;

class Radio extends Base {

  static function render($block) {
    $html .= '<p class="mailpoet_paragraph">';

    $html .= static::renderLabel($block);

    $html .= '<select class="mailpoet_select" name="'.static::getFieldName($block).'">';

    if(isset($block['params']['label_within']) && $block['params']['label_within']) {
      $html .= '<option value="">'.static::getFieldLabel($block).'</option>';
    }

    foreach($block['params']['values'] as $option) {
      $is_selected = (isset($option['is_checked']) && $option['is_checked']) ? 'selected="selected"' : '';
      $html .= '<option value="'.$option['value'].'" '.$is_selected.'>'.$option['value'].'</option>';
    }
    $html .= '</select>';

    $html .= '</p>';

    return $html;
  }
}