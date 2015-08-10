<?php
namespace MailPoet\Form\Block;

class Submit extends Base {

  static function render($block) {
    $html = '';

    // open input
    $html .= '<input class="mailpoet_submit" type="submit" ';

    // input value
    $html .= 'value="'.static::getFieldLabel($block).'" ';

    // close input
    $html .= '/>';

    return $html;
  }
}