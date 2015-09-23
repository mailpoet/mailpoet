<?php namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;

class Image {
  static function render($element) {
    $stylesHelper = new StylesHelper();

    $element['width'] = (int) $element['width'];

    $template = '
    <tr>
      <td class="mailpoet_col mailpoet_image ' . (($element['padded'] === true) ? "mailpoet_padded" : "") . '"
          style="' . $stylesHelper->getBlockStyles($element) . '"
          valign="top">
        <img style="top:0; left:0; height: auto; width:100%;"
             src="' . $element['src'] . '"
             width="' . (($element['padded'] === true) ? $element['width'] - (20 * 2) : $element['width']) . '">
      </td>
    </tr>';

    return $template;
  }
}