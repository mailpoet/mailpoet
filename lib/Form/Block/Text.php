<?php

namespace MailPoet\Form\Block;

use MailPoet\Form\TextInputStylesRenderer;

class Text {

  /** @var BlockRendererHelper */
  private $rendererHelper;

  /** @var TextInputStylesRenderer */
  private $inputStylesRenderer;

  public function __construct(BlockRendererHelper $rendererHelper, TextInputStylesRenderer $inputStylesRenderer) {
    $this->rendererHelper = $rendererHelper;
    $this->inputStylesRenderer = $inputStylesRenderer;
  }

  public function render(array $block, array $formSettings): string {
    $type = 'text';
    $automationId = ' ';
    if ($block['id'] === 'email') {
      $type = 'email';
    }
    $styles = $this->inputStylesRenderer->render($block['styles'] ?? []);

    if (in_array($block['id'], ['email', 'last_name', 'first_name'], true)) {
      $automationId = 'data-automation-id="form_' . $block['id'] . '" ';
    }

    $html = '<div class="mailpoet_paragraph">';

    $html .= $this->rendererHelper->renderLabel($block, $formSettings);

    $html .= '<input type="' . $type . '" class="mailpoet_text" ';

    $html .= 'name="data[' . $this->rendererHelper->getFieldName($block) . ']" ';

    $html .= 'title="' . $this->rendererHelper->getFieldLabel($block) . '" ';

    $html .= 'value="' . $this->rendererHelper->getFieldValue($block) . '" ';

    if ($styles) {
      $html .= 'style="' . $styles . '" ';
    }

    $html .= $automationId;

    $html .= $this->rendererHelper->renderInputPlaceholder($block);

    $html .= $this->rendererHelper->getInputValidation($block);

    $html .= $this->rendererHelper->getInputModifiers($block);

    $html .= '/>';

    $html .= '</div>';

    return $html;
  }
}
