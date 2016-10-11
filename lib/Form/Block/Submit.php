<?php
namespace MailPoet\Form\Block;

if(!defined('ABSPATH')) exit;

class Submit extends Base {

  static function render($block) {
    $html = '';

    $html .= '<p class="mailpoet_submit"><input type="submit" ';

    $html .= 'value="'.static::getFieldLabel($block).'" ';

    $html .= '/></p>';

    return $html;
  }
}