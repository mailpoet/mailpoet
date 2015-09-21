<?php namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;

class Spacer {

  static function render($element) {

    $stylesHelper = new StylesHelper();

    // if the parent container (column) has background set and the divider element has a transparent background,
    // it will assume the newsletter background, not that of the parent container
    if($element['styles']['block']['backgroundColor'] === 'transparent') {
      unset($element['styles']['block']['backgroundColor']);
    }

    $template = '
    <tr>
      <td class="mailpoet_col mailpoet_spacer" style="' . $stylesHelper->getBlockStyles($element) . '" valign="top"> </td>
    </tr>';

    return $template;
  }

}