<?php

namespace MailPoet\Form\Block;

use MailPoet\Form\BlockStylesRenderer;
use MailPoet\Form\BlockWrapperRenderer;

class Textarea {
  /** @var BlockRendererHelper */
  private $rendererHelper;

  /** @var BlockStylesRenderer */
  private $inputStylesRenderer;

   /** @var BlockWrapperRenderer */
  private $wrapper;

  public function __construct(
    BlockRendererHelper $rendererHelper,
    BlockStylesRenderer $inputStylesRenderer,
    BlockWrapperRenderer $wrapper
  ) {
    $this->rendererHelper = $rendererHelper;
    $this->inputStylesRenderer = $inputStylesRenderer;
    $this->wrapper = $wrapper;
  }

  public function render(array $block, array $formSettings): string {
    $html = '';
    $name = $this->rendererHelper->getFieldName($block);
    $styles = $this->inputStylesRenderer->renderForTextInput($block['styles'] ?? [], $formSettings);

    $html .= $this->rendererHelper->renderLabel($block, $formSettings);

    $lines = (isset($block['params']['lines']) ? (int)$block['params']['lines'] : 1);
    if (
      isset($block['params']['label_within'])
      && $block['params']['label_within']
      && isset($block['styles']['font_color'])
    ) {
      $html .= '<style>'
        . 'textarea[name="data[' . $name . ']"]::placeholder{'
        . 'color:' . $block['styles']['font_color'] . ';'
        . 'opacity: 1;'
        . '}'
        . '</style>';
    }

    $html .= '<textarea class="mailpoet_textarea" rows="' . $lines . '" ';

    $html .= 'name="data[' . $name . ']"';

    $html .= $this->rendererHelper->renderInputPlaceholder($block);

    $html .= $this->rendererHelper->getInputValidation($block);

    $html .= $this->rendererHelper->getInputModifiers($block);

    if ($styles) {
      $html .= 'style="' . $styles . '" ';
    }

    $html .= '>' . $this->rendererHelper->getFieldValue($block) . '</textarea>';

    return $this->wrapper->render($block, $html);
  }
}
