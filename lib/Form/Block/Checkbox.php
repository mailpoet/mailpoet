<?php

namespace MailPoet\Form\Block;

use MailPoet\WP\Functions as WPFunctions;

class Checkbox {

  /** @var Base */
  private $baseRenderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(Base $baseRenderer, WPFunctions $wp) {
    $this->baseRenderer = $baseRenderer;
    $this->wp = $wp;
  }

  public function render(array $block): string {
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

      $html .= ' /> ' . $this->wp->escAttr($option['value']);

      $html .= '</label>';
    }

    $html .= '<span class="mailpoet_error_' . $block['id'] . '"></span>';

    $html .= '</p>';

    return $html;
  }
}
