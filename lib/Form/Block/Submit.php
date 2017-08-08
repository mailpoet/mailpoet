<?php
namespace MailPoet\Form\Block;

if(!defined('ABSPATH')) exit;

class Submit extends Base {

  static function render($block) {
    $html = '';

    $html .= '<p class="mailpoet_paragraph"><input type="submit" class="mailpoet_submit" ';

    $html .= 'value="'.static::getFieldLabel($block).'" ';

    $html .= '/></p>';

    return $html;
  }
}