<?php

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;

class Spacer {
  public function render($element) {
    $height = (int)$element['styles']['block']['height'];
    $backgroundColor = EHelper::escapeHtmlAttr($element['styles']['block']['backgroundColor']);
    $template = '
      <tr>
        <td class="mailpoet_spacer" ' .
      (($backgroundColor !== 'transparent') ? 'bgcolor="' . $backgroundColor . '" ' : '') .
      'height="' . $height . '" valign="top"></td>
      </tr>';
    return $template;
  }
}
