<?php

namespace MailPoet\Form\Block;

use MailPoet\Form\BlockWrapperRenderer;

class Submit {

  /** @var BlockRendererHelper */
  private $rendererHelper;

  /** @var BlockWrapperRenderer */
  private $wrapper;

  public function __construct(BlockRendererHelper $rendererHelper, BlockWrapperRenderer $wrapper) {
    $this->rendererHelper = $rendererHelper;
    $this->wrapper = $wrapper;
  }

  public function render(array $block): string {
    $html = '';

    $html .= '<input type="submit" class="mailpoet_submit" ';

    $html .= 'value="' . $this->rendererHelper->getFieldLabel($block) . '" ';

    $html .= 'data-automation-id="subscribe-submit-button" ';

    $html .= '/>';

    $html .= '<span class="mailpoet_form_loading"><span class="mailpoet_bounce1"></span><span class="mailpoet_bounce2"></span><span class="mailpoet_bounce3"></span></span>';

    return $this->wrapper->render($block, $html);
  }
}
