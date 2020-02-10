<?php

namespace MailPoet\Form\Block;

class Segment {

  /** @var Base */
  private $baseRenderer;

  public function __construct(Base $baseRenderer) {
    $this->baseRenderer = $baseRenderer;
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

    foreach ($options as $option) {
      if (!isset($option['id']) || !isset($option['name'])) continue;

      $isChecked = (isset($option['is_checked']) && $option['is_checked']) ? 'checked="checked"' : '';

      $html .= '<label class="mailpoet_checkbox_label">';
      $html .= '<input type="checkbox" class="mailpoet_checkbox" ';
      $html .= 'name="' . $fieldName . '[]" ';
      $html .= 'value="' . $option['id'] . '" ' . $isChecked . ' ';
      $html .= $fieldValidation;
      $html .= ' /> ' . esc_attr($option['name']);
      $html .= '</label>';
    }

    $html .= '<span class="mailpoet_error_' . $block['id'] . '"></span>';

    $html .= '</p>';

    return $html;
  }
}
