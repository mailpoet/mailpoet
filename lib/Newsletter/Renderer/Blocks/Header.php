<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;
use MailPoet\Util\pQuery\pQuery;

class Header {
  static function render($element) {
    $element['text'] = preg_replace('/\n/', '<br />', $element['text']);
    $element['text'] = preg_replace('/(<\/?p.*?>)/i', '', $element['text']);
    $line_height = sprintf(
      '%spx', StylesHelper::$line_height_multiplier * (int)$element['styles']['text']['fontSize']
    );
    $DOM_parser = new pQuery();
    $DOM = $DOM_parser->parseStr($element['text']);
    if(isset($element['styles']['link'])) {
      $links = $DOM->query('a');
      if($links->count()) {
        foreach($links as $link) {
          $link->style = StylesHelper::getStyles($element['styles'], 'link');
        }
      }
    }
    $background_color = $element['styles']['block']['backgroundColor'];
    $background_color = ($background_color !== 'transparent') ?
      'bgcolor="' . $background_color . '"' :
      false;
    if(!$background_color) unset($element['styles']['block']['backgroundColor']);
    $template = '
      <tr>
        <td class="mailpoet_header_footer_padded mailpoet_header" ' . $background_color . '
        style="line-height: ' . $line_height  . ';' . StylesHelper::getBlockStyles($element) . StylesHelper::getStyles($element['styles'], 'text') . '">
          ' . $DOM->html() . '
        </td>
      </tr>';
    return $template;
  }
}