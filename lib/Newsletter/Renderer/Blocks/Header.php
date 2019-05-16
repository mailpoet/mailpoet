<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;
use MailPoet\Util\CSS;
use MailPoet\Util\pQuery\pQuery;
use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;

class Header {
  static function render($element) {
    $element['text'] = preg_replace('/\n/', '<br />', $element['text']);
    $element['text'] = preg_replace('/(<\/?p.*?>)/i', '', $element['text']);
    $line_height = sprintf(
      '%spx', StylesHelper::$default_line_height * (int)$element['styles']['text']['fontSize']
    );
    $DOM_parser = new pQuery();
    $DOM = $DOM_parser->parseStr($element['text']);
    if (isset($element['styles']['link'])) {
      $links = $DOM->query('a');
      if ($links->count()) {
        $css = new CSS();
        foreach ($links as $link) {
          $element_link_styles = StylesHelper::getStyles($element['styles'], 'link');
          $link->style = $css->mergeInlineStyles($element_link_styles, $link->style);
        }
      }
    }
    $background_color = $element['styles']['block']['backgroundColor'];
    $background_color = ($background_color !== 'transparent') ?
      'bgcolor="' . $background_color . '"' :
      false;
    if (!$background_color) unset($element['styles']['block']['backgroundColor']);
    $style = 'line-height: ' . $line_height . ';' . StylesHelper::getBlockStyles($element) . StylesHelper::getStyles($element['styles'], 'text');
    $style = EHelper::escapeHtmlStyleAttr($style);
    $template = '
      <tr>
        <td class="mailpoet_header_footer_padded mailpoet_header" ' . $background_color . ' style="' . $style . '">
          ' . str_replace('&', '&amp;', $DOM->html()) . '
        </td>
      </tr>';
    return $template;
  }
}
