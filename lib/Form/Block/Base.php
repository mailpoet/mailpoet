<?php
namespace MailPoet\Form\Block;

abstract class Base {
  protected static function getInputValidation($block) {
    $rules = array();
    $is_required = false;

    if($block['id'] === 'email') {
      $is_required = true;
      $rules[] = 'custom[email]';
    }

    if($block['id'] === 'segments') {
      $is_required = true;
    }

    if(!empty($block['params']['required'])) {
      $is_required = true;

    }

    if(isset($block['params']['validate'])) {
      if(is_array($block['params']['validate'])) {
        // handle multiple validation rules
        foreach($block['params']['validate'] as $rule) {
          $rules[] = 'custom['.$rule.']';
        }
      } else if(strlen(trim($block['params']['validate'])) > 0) {
        // handle single validation rule
        $rules[] = 'custom['.$block['params']['validate'].']';
      }
    }

    // generate string if there is at least one rule to validate against
    $validation = '';

    if(!empty($rules)) {
      $rules = array_unique($rules);
      //return 'validate['.join(',', $rules).']';
      // TODO: convert to Parsley format!
    }

    if($is_required === true) {
      $validation .= ' required';
    }

    return $validation;
  }

  protected static function renderLabel($block) {
    $html = '';
    // if the label is displayed as a placeholder, we don't display a label outside
    if(isset($block['params']['label_within'])
    && $block['params']['label_within']) {
      return $html;
    }
    if(isset($block['params']['label'])
      && strlen(trim($block['params']['label'])) > 0) {
      $html .= '<label class="mailpoet_'.$block['type'].'_label">'.$block['params']['label'];

      if(isset($block['params']['required']) && $block['params']['required']) {
        $html .= ' <span class="mailpoet_required">*</span>';
      }

      $html .= '</label>';
    }
    return $html;
  }

  protected static function renderInputPlaceholder($block) {
    $html = '';
    // if the label is displayed as a placeholder,
    if(isset($block['params']['label_within']) && $block['params']['label_within']) {
      // display only label
      $html .= ' placeholder="';
      $html .= static::getFieldLabel($block);
      // add an asterisk if it's a required field
      if(isset($block['params']['required']) && $block['params']['required']) {
        $html .= ' *';
      }
      $html .= '" ';
    }
    return $html;
  }

  // return field name depending on block data
  protected static function getFieldName($block = array()) {
    return $block['id'];
  }

  protected static function getFieldLabel($block = array()) {
    return (isset($block['params']['label'])
            && strlen(trim($block['params']['label'])) > 0)
            ? trim($block['params']['label']) : '';
  }

  protected static function getFieldValue($block = array()) {
    return (isset($block['params']['value'])
            && strlen(trim($block['params']['value'])) > 0)
            ? trim($block['params']['value']) : '';
  }
}