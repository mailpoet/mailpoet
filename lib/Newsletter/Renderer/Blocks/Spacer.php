<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

class Spacer {
  static function render($element) {
    $template = '
      <tr>
        <td class="mailpoet_spacer" height="' . (int) $element['styles']['block']['height'] . '" valign="top"></td>
      </tr>';
    return $template;
  }
}