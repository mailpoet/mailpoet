<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;

class Footer {
  static function render($element) {
    $element['text'] = preg_replace('/\n/', '<br /><br />', $element['text']);
    $element['text'] = preg_replace('/(<\/?p.*?>)/i', '', $element['text']);
    $DOM_parser = new \pQuery();
    $DOM = $DOM_parser->parseStr($element['text']);
    if(isset($element['styles']['link'])) {
      $links = $DOM->query('a');
      if($links->count()) {
        foreach($links as $link) {
          $link->style = StylesHelper::getStyles($element['styles'], 'link');
        }
      }
    }
    $template = '
      <tr>
        <td class="mailpoet_header_footer_padded mailpoet_footer" bgcolor="' . $element['styles']['block']['backgroundColor'] . '"
        style="line-height: ' . StylesHelper::$line_height . ';' . StylesHelper::getBlockStyles($element) . StylesHelper::getStyles($element['styles'], 'text') . '">
          ' . $DOM->html() . '
        </td>
      </tr>';
    return $template;
  }
}