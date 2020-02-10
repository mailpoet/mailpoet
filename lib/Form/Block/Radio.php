<?php

namespace MailPoet\Form\Block;

use MailPoet\WP\Functions as WPFunctions;

class Radio {

  /** @var Base */
  private $baseRenderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(Base $baseRenderer, WPFunctions $wp) {
    $this->baseRenderer = $baseRenderer;
    $this->wp = $wp;
  }

  public function render($block) {
    $html = '';

    $fieldName = 'data[' . $this->baseRenderer->getFieldName($block) . ']';
    $fieldValidation = $this->baseRenderer->getInputValidation($block);

    $html .= '<p class="mailpoet_paragraph">';

    $html .= $this->baseRenderer->renderLabel($block);

    $options = (!empty($block['params']['values'])
      ? $block['params']['values']
      : []
    );

    $selectedValue = $this->baseRenderer->getFieldValue($block);

    foreach ($options as $option) {
      $html .= '<label class="mailpoet_radio_label">';

      $html .= '<input type="radio" class="mailpoet_radio" ';

      $html .= 'name="' . $fieldName . '" ';

      if (is_array($option['value'])) {
        $value = key($option['value']);
        $label = reset($option['value']);
      } else {
        $value = $option['value'];
        $label = $option['value'];
      }

      $html .= 'value="' . $this->wp->escAttr($value) . '" ';

      $html .= (
        (
          $selectedValue === ''
          && isset($option['is_checked'])
          && $option['is_checked']
        ) || ($selectedValue === $value)
      ) ? 'checked="checked"' : '';

      $html .= $fieldValidation;
      $html .= ' /> ' . $this->wp->escAttr($label);
      $html .= '</label>';
    }

    $html .= '<span class="mailpoet_error_' . $block['id'] . '"></span>';

    $html .= '</p>';

    return $html;
  }
}
