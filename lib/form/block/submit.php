<?php
namespace MailPoet\Form\Block;

class Submit extends Base {

  static function render($block) {
    $html = '';

    $html .= '<input class="mailpoet_submit" type="submit" ';

    $html .= 'value="'.static::getFieldLabel($block).'" ';

    $html .= '/>';

    return $html;
  }
}