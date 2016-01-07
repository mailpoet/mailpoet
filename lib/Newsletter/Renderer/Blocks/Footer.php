<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;

class Footer {
  static function render($element) {
    if(isset($element['styles']['link'])) {
      $element['text'] = str_replace(
        '<a',
        '<a style="'
        . StylesHelper::getStyles($element['styles'], 'link')
        . '"', $element['text']
      );
    }
    $element['text'] = preg_replace('/\n/', '<br /><br />', $element['text']);
    $element['text'] = preg_replace('/(<\/?p>)/', '', $element['text']);
    $template = '
      <tr>
        <td class="mailpoet_padded_header_footer mailpoet_footer" bgcolor="' . $element['styles']['block']['backgroundColor'] . '"
        style="' . StylesHelper::getBlockStyles($element) . StylesHelper::getStyles($element['styles'], 'text') . '">
        ' . $element['text'] . '
        </td>
      </tr>';
    return $template;
  }
}