<?php namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;

class Header {
  static function render($element) {
    $stylesHelper = new StylesHelper();

    // apply link styles
    if(isset($element['styles']['link'])) {
      $element['text'] = str_replace('<a', '<a style="' . $stylesHelper->getStyles($element['styles'], 'link') . '"', $element['text']);
    }
    
    // apply text styles
    if(isset($element['styles']['link'])) {
      $element['text'] = str_replace('<p', '<p style="' . $stylesHelper->getStyles($element['styles'], 'text') . '"', $element['text']);
    }
    
    $template = '
    <tr>
      <td class="mailpoet_col mailpoet_header"
          style="' . $stylesHelper->getBlockStyles($element) . '"
          valign="top">
        <div>' . $element['text'] . '</div>
      </td>
    </tr>';
    
    return $template;
  }
}