<?php

namespace MailPoet\Form\Block;

use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\WP\Functions as WPFunctions;

class Segment {

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

    foreach ($options as $option) {
      if (!isset($option['id']) || !isset($option['name'])) continue;

      $isChecked = (isset($option['is_checked']) && $option['is_checked']) ? 'checked="checked"' : '';

      $html .= '<label class="mailpoet_checkbox_label" '
        . $this->rendererHelper->renderFontStyle($formSettings)
        . '>';
      $html .= '<input type="checkbox" class="mailpoet_checkbox" ';
      $html .= 'name="' . $fieldName . '[]" ';
      $html .= 'value="' . $option['id'] . '" ' . $isChecked . ' ';
      $html .= $fieldValidation;
      $html .= ' /> ' . $this->wp->escAttr($option['name']);
      $html .= '</label>';
    }

    $html .= '<span class="mailpoet_error_' . $block['id'] . '"></span>';

    return $this->wrapper->render($block, $html);
  }
}
