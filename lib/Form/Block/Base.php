<?php
namespace MailPoet\Form\Block;

abstract class Base {
  protected static function getInputValidation($block) {
    $rules = array();

    if($block['id'] === 'email') {
      $rules['required'] = true;
      $rules['error-message'] = __('You need to specify a valid email address');
    }

    if($block['id'] === 'segments') {
      $rules['required'] = true;
      $rules['mincheck'] = 1;
      $rules['error-message'] = __('You need to select a list');
    }

    if(!empty($block['params']['required'])) {
      $rules['required'] = true;
    }

    if(!empty($block['params']['validate'])) {
      if($block['params']['validate'] === 'phone') {
        $rules['pattern'] = "^[\d\+\-\.\(\)\/\s]*$";
        $rules['error-message'] = __('You need to specify a valid phone number');
      } else {
        $rules['type'] = $block['params']['validate'];
      }
    }

    if($block['type'] === 'radio') {
      $rules['group'] = 'custom_field_'.$block['id'];
      $rules['errors-container'] = '.mailpoet_error_'.$block['id'];
      $rules['required-message'] = __('You need to select at least one option.');
    }

    $validation = array();

    if(!empty($rules)) {
      $rules = array_unique($rules);
      foreach($rules as $rule => $value) {
        if(is_bool($value)) {
          $value = ($value) ? 'true' : 'false';
        }
        $validation[] = 'data-parsley-'.$rule.'="'.$value.'"';
      }
    }
    return join(' ', $validation);
  }

  protected static function renderLabel($block) {
    $html = '';
    if(
      isset($block['params']['label_within'])
      && $block['params']['label_within']
    ) {
      return $html;
    }
    if(isset($block['params']['label'])
      && strlen(trim($block['params']['label'])) > 0) {
      $html .= '<label class="mailpoet_'.$block['type'].'_label">';
      $html .= $block['params']['label'];

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
    if(
      isset($block['params']['label_within'])
      && $block['params']['label_within']
    ) {
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
    if((int)$block['id'] > 0) {
      return 'cf_'.$block['id'];
    } else {
      return $block['id'];
    }
  }

  protected static function getFieldLabel($block = array()) {
    return (isset($block['params']['label'])
            && strlen(trim($block['params']['label'])) > 0)
            ? trim($block['params']['label']) : '';
  }

  protected static function getFieldValue($block = array()) {
    return (isset($block['params']['value'])
            && strlen(trim($block['params']['value'])) > 0)
            ? esc_attr(trim($block['params']['value'])) : '';
  }
}