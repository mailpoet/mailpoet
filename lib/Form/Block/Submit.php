<?php

namespace MailPoet\Form\Block;

class Submit {

  /** @var BlockRendererHelper */
  private $rendererHelper;

  public function __construct(BlockRendererHelper $rendererHelper) {
    $this->rendererHelper = $rendererHelper;
  }

  public function render(array $block): string {
    $html = '';

    $html .= '<div class="mailpoet_paragraph"><input type="submit" class="mailpoet_submit" ';

    $html .= 'value="' . $this->rendererHelper->getFieldLabel($block) . '" ';

    $html .= 'data-automation-id="subscribe-submit-button" ';

    $html .= '/>';

    $html .= '<span class="mailpoet_form_loading"><span class="mailpoet_bounce1"></span><span class="mailpoet_bounce2"></span><span class="mailpoet_bounce3"></span></span>';

    $html .= '</div>';

    return $html;
  }
}
