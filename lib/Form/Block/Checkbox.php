<?php

namespace MailPoet\Form\Block;

class Checkbox extends Base {

  public function render($block) {
    $html = '';

    $fieldName = 'data[' . $this->getFieldName($block) . ']';
    $fieldValidation = $this->getInputValidation($block);

    $html .= '<p class="mailpoet_paragraph">';

    $html .= $this->renderLabel($block);

    $options = (!empty($block['params']['values'])
      ? $block['params']['values']
      : []
    );

    $selectedValue = self::getFieldValue($block);

    foreach ($options as $option) {
      $html .= '<label class="mailpoet_checkbox_label">';
      $html .= '<input type="checkbox" class="mailpoet_checkbox" ';

      $html .= 'name="' . $fieldName . '" ';

      $html .= 'value="1" ';

      $html .= (
        (
          $selectedValue === ''
          && isset($option['is_checked'])
          && $option['is_checked']
        ) || ($selectedValue)
      ) ? 'checked="checked"' : '';

      $html .= $fieldValidation;

      $html .= ' /> ' . esc_attr($option['value']);

      $html .= '</label>';
    }

    $html .= '<span class="mailpoet_error_' . $block['id'] . '"></span>';

    $html .= '</p>';

    return $html;
  }
}
