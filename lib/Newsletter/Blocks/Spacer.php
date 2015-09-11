<?php namespace MailPoet\Newsletter\Blocks;

use MailPoet\Newsletter\Blocks\Renderer as BlocksRenderer;

class Spacer {

  static function render($element) {

    $blocksRenderer = new Renderer();

    // if the parent container (column) has background set and the divider element has a transparent background,
    // it will assume the newsletter background, not that of the parent container
    if($element['styles']['block']['backgroundColor'] === 'transparent') {
      unset($element['styles']['block']['backgroundColor']);
    }

    $template = '
<tr>
  <td class="mailpoet_col mailpoet_spacer" style="' . $blocksRenderer->getBlockStyles($element) . '" valign="top"> </td>
</tr>';

    return $template;
  }

}