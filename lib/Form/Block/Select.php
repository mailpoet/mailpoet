<?php

namespace MailPoet\Form\Block;

class Select extends Base {

  public function render($block) {
    $html = '';

    $fieldName = 'data[' . $this->getFieldName($block) . ']';
    $fieldValidation = $this->getInputValidation($block);
    $automationId = ($block['id'] == 'status') ? 'data-automation-id="form_status"' : '';
    $html .= '<p class="mailpoet_paragraph">';
    $html .= $this->renderLabel($block);
    $html .= '<select class="mailpoet_select" name="' . $fieldName . '" ' . $automationId . '>';

    if (isset($block['params']['label_within']) && $block['params']['label_within']) {
      $label = $this->getFieldLabel($block);
      if (!empty($block['params']['required'])) {
        $label .= ' *';
      }
      $html .= '<option value="" disabled selected hidden>' . $label . '</option>';
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

      $isSelected = (
        (isset($option['is_checked']) && $option['is_checked'])
        ||
        (self::getFieldValue($block) === $option['value'])
      ) ? ' selected="selected"' : '';

      $isDisabled = (!empty($option['is_disabled'])) ? ' disabled="disabled"' : '';

      if (is_array($option['value'])) {
        $value = key($option['value']);
        $label = reset($option['value']);
      } else {
        $value = $option['value'];
        $label = $option['value'];
      }

      $html .= '<option value="' . $value . '"' . $isSelected . $isDisabled . '>';
      $html .= $this->wp->escAttr($label);
      $html .= '</option>';
    }
    $html .= '</select>';

    $html .= '</p>';

    return $html;
  }
}
