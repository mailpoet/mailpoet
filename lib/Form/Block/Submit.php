<?php

namespace MailPoet\Form\Block;

class Submit extends Base {

  public function render($block) {
    $html = '';

    $html .= '<p class="mailpoet_paragraph"><input type="submit" class="mailpoet_submit" ';

    $html .= 'value="' . $this->getFieldLabel($block) . '" ';

    $html .= 'data-automation-id="subscribe-submit-button" ';

    $html .= '/>';

    $html .= '<span class="mailpoet_form_loading"><span class="mailpoet_bounce1"></span><span class="mailpoet_bounce2"></span><span class="mailpoet_bounce3"></span></span>';

    $html .= '</p>';

    return $html;
  }
}
