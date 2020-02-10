<?php

namespace MailPoet\Form\Block;

class Submit {

  /** @var Base */
  private $baseRenderer;

  public function __construct(Base $baseRenderer) {
    $this->baseRenderer = $baseRenderer;
  }

  public function render(array $block): string {
    $html = '';

    $html .= '<p class="mailpoet_paragraph"><input type="submit" class="mailpoet_submit" ';

    $html .= 'value="' . $this->baseRenderer->getFieldLabel($block) . '" ';

    $html .= 'data-automation-id="subscribe-submit-button" ';

    $html .= '/>';

    $html .= '<span class="mailpoet_form_loading"><span class="mailpoet_bounce1"></span><span class="mailpoet_bounce2"></span><span class="mailpoet_bounce3"></span></span>';

    $html .= '</p>';

    return $html;
  }
}
