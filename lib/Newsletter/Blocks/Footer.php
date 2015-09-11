<?php namespace MailPoet\Newsletter\Blocks;

class Footer {

  static function render($element) {
    $blocksRenderer = new Renderer();

    // apply link styles
    if(isset($element['styles']['link'])) {
      $element['text'] = str_replace('<a', '<a style="' . $blocksRenderer->getStyles($element['styles'], 'link') . '"', $element['text']);
    }

    // apply text styles
    if(isset($element['styles']['link'])) {
      $element['text'] = str_replace('<p', '<p style="' . $blocksRenderer->getStyles($element['styles'], 'text') . '"', $element['text']);
    }

    $template = '
<tr>
  <td class="mailpoet_col mailpoet_footer" style="' . $blocksRenderer->getStyles($element['styles'], 'block') . '" valign="top">
    <div>' . $element['text'] . '</div>
  </td>
</tr>';

    return $template;
  }

}