<?php namespace MailPoet\Newsletter\Blocks;

use MailPoet\Newsletter\Blocks\Renderer as BlocksRenderer;

class Footer {

  static function render($element) {
    // apply link styles
    if(isset($element['styles']['link'])) {
      $element['text'] = str_replace('<a', '<a style="' . BlocksRenderer::getStyles($element['styles'], 'link') . '"', $element['text']);
    }

    // apply text styles
    if(isset($element['styles']['link'])) {
      $element['text'] = str_replace('<p', '<p style="' . BlocksRenderer::getStyles($element['styles'], 'text') . '"', $element['text']);
    }

    $template = '
<tr>
  <td class="mailpoet_col mailpoet_footer" style="' . BlocksRenderer::getStyles($element['styles'], 'block') . '" valign="top">
    <div>' . $element['text'] . '</div>
  </td>
</tr>';

    return $template;
  }

}