<?php

namespace MailPoet\Form\Block;

use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\WP\Functions as WPFunctions;

class Checkbox {

  /** @var BlockRendererHelper */
  private $rendererHelper;

  /** @var BlockWrapperRenderer */
  private $wrapper;

  /** @var WPFunctions */
  private $wp;

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
      $html .= '<label class="mailpoet_checkbox_label" '
        . $this->rendererHelper->renderFontStyle($formSettings) . '>';
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

      $html .= ' /> ' . $option['value'];

      $html .= '</label>';
    }

    $html .= '<span class="mailpoet_error_' . $this->wp->escAttr($block['id']) . '"></span>';

    return $this->wrapper->render($block, $html);
  }
}
