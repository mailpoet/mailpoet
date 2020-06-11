<?php

namespace MailPoet\Form\Block;

use MailPoet\Form\BlockStylesRenderer;
use MailPoet\Form\BlockWrapperRenderer;

class Submit {

  /** @var BlockRendererHelper */
  private $rendererHelper;

  /** @var BlockWrapperRenderer */
  private $wrapper;

  /** @var BlockStylesRenderer */
  private $stylesRenderer;

  public function __construct(BlockRendererHelper $rendererHelper, BlockWrapperRenderer $wrapper, BlockStylesRenderer $stylesRenderer) {
    $this->rendererHelper = $rendererHelper;
    $this->wrapper = $wrapper;
    $this->stylesRenderer = $stylesRenderer;
  }

  public function render(array $block, array $formSettings): string {
    $html = '';

    $html .= '<input type="submit" class="mailpoet_submit" ';

    $html .= 'value="' . $this->rendererHelper->getFieldLabel($block) . '" ';

    $html .= 'data-automation-id="subscribe-submit-button" ';

    if (isset($block['styles']['font_family'])) {
      $html .= "data-font-family='{$block['styles']['font_family']}' " ;
    }

    $styles = $this->stylesRenderer->renderForButton($block['styles'] ?? [], $formSettings);

    if ($styles) {
      $html .= 'style="' . $styles . '" ';
    }

    $html .= '/>';

    $html .= '<span class="mailpoet_form_loading"><span class="mailpoet_bounce1"></span><span class="mailpoet_bounce2"></span><span class="mailpoet_bounce3"></span></span>';

    return $this->wrapper->render($block, $html);
  }
}
