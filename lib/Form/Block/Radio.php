<?php

namespace MailPoet\Form\Block;

use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\WP\Functions as WPFunctions;

class Radio {

  /** @var BlockRendererHelper */
  private $rendererHelper;

  /** @var WPFunctions */
  private $wp;

  /** @var BlockWrapperRenderer */
  private $wrapper;

  public function __construct(BlockRendererHelper $rendererHelper, BlockWrapperRenderer $wrapper, WPFunctions $wp) {
    $this->rendererHelper = $rendererHelper;
    $this->wrapper = $wrapper;
    $this->wp = $wp;
  }

  public function render(array $block, array $formSettings): string {
    $html = '';

    $fieldName = 'data[' . $this->rendererHelper->getFieldName($block) . ']';
    $fieldValidation = $this->rendererHelper->getInputValidation($block);

    $html .= $this->rendererHelper->renderLabel($block, $formSettings);

    $options = (!empty($block['params']['values'])
      ? $block['params']['values']
      : []
    );

    $selectedValue = $this->rendererHelper->getFieldValue($block);

    foreach ($options as $option) {
      $html .= '<label class="mailpoet_radio_label" '
        . $this->rendererHelper->renderFontStyle($formSettings)
        . '>';

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

    return $this->wrapper->render($block, $html);
  }
}
