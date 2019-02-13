<?php
namespace MailPoet\Form\Block;

class Html {

  static function render($block) {
    $html = '';
    $text = '';

    if (isset($block['params']['text']) && $block['params']['text']) {
      $text = html_entity_decode($block['params']['text'], ENT_QUOTES);
    }

    if (isset($block['params']['nl2br']) && $block['params']['nl2br']) {
      $text = nl2br($text);
    }

    $html .= '<p class="mailpoet_paragraph">';
    $html .= $text;
    $html .= '</p>';

    return $html;
  }
}
