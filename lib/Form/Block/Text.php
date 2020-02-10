<?php

namespace MailPoet\Form\Block;

class Text {

  /** @var Base */
  private $baseRenderer;

  public function __construct(Base $baseRenderer) {
    $this->baseRenderer = $baseRenderer;
  }

  public function render($block) {
    $type = 'text';
    $automationId = ' ';
    if ($block['id'] === 'email') {
      $type = 'email';
      $automationId = 'data-automation-id="form_email" ';
    }

    $html = '<p class="mailpoet_paragraph">';

    $html .= $this->baseRenderer->renderLabel($block);

    $html .= '<input type="' . $type . '" class="mailpoet_text" ';

    $html .= 'name="data[' . $this->baseRenderer->getFieldName($block) . ']" ';

    $html .= 'title="' . $this->baseRenderer->getFieldLabel($block) . '" ';

    $html .= 'value="' . $this->baseRenderer->getFieldValue($block) . '" ';

    $html .= $automationId;

    $html .= $this->baseRenderer->renderInputPlaceholder($block);

    $html .= $this->baseRenderer->getInputValidation($block);

    $html .= $this->baseRenderer->getInputModifiers($block);

    $html .= '/>';

    $html .= '</p>';

    return $html;
  }
}
