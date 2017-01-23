<?php
namespace MailPoet\Form\Block;

abstract class Base {
  protected static function getInputValidation($block, $extra_rules = array()) {
    $rules = array();

    if($block['id'] === 'email') {
      $rules['required'] = true;
      $rules['error-message'] = __('Please specify a valid email address.', 'mailpoet');
    }

    if($block['id'] === 'segments') {
      $rules['required'] = true;
      $rules['mincheck'] = 1;
      $rules['group'] = $block['id'];
      $rules['errors-container'] = '.mailpoet_error_'.$block['id'];
      $rules['required-message'] = __('Please select a list', 'mailpoet');
    }

    if(!empty($block['params']['required'])) {
      $rules['required'] = true;
    }

    if(!empty($block['params']['validate'])) {
      if($block['params']['validate'] === 'phone') {
        $rules['pattern'] = "^[\d\+\-\.\(\)\/\s]*$";
        $rules['error-message'] = __('Please specify a valid phone number', 'mailpoet');
      } else {
        $rules['type'] = $block['params']['validate'];
      }
    }

    if(in_array($block['type'], array('radio', 'checkbox'))) {
      $rules['group'] = 'custom_field_'.$block['id'];
      $rules['errors-container'] = '.mailpoet_error_'.$block['id'];
      $rules['required-message'] = __('Please select at least one option', 'mailpoet');
    }

    if($block['type'] === 'date') {
      $rules['group'] = 'custom_field_'.$block['id'];
      $rules['errors-container'] = '.mailpoet_error_'.$block['id'];
    }

    $validation = array();

    $rules = array_merge($rules, $extra_rules);

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

  protected static function getInputModifiers($block = array()) {
    $modifiers = array();

    if(isset($block['params']['readonly']) && $block['params']['readonly']) {
      $modifiers[] = 'readonly';
    }

    if(isset($block['params']['disabled']) && $block['params']['disabled']) {
      $modifiers[] = 'disabled';
    }
    return join(' ', $modifiers);
  }
}
