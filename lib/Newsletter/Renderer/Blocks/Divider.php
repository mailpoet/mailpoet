<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;
use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;

class Divider {
  static function render($element) {
    $background_color = $element['styles']['block']['backgroundColor'];
    $template = '
      <tr>
        <td class="mailpoet_divider" valign="top" ' .
        (($element['styles']['block']['backgroundColor'] !== 'transparent') ?
          'bgColor="' . EHelper::escapeHtmlAttr($background_color) . '" style="background-color:' . EHelper::escapeHtmlStyleAttr($background_color) . ';' :
          'style="'
        ) .
      sprintf('padding: %s %spx %s %spx;',
              EHelper::escapeHtmlStyleAttr($element['styles']['block']['padding']),
              StylesHelper::$padding_width,
              EHelper::escapeHtmlStyleAttr($element['styles']['block']['padding']),
              StylesHelper::$padding_width) . '">
          <table width="100%" border="0" cellpadding="0" cellspacing="0"
          style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;">
            <tr>
              <td class="mailpoet_divider-cell"
              style="border-top-width: ' . EHelper::escapeHtmlStyleAttr($element['styles']['block']['borderWidth']) . ';
                     border-top-style: ' . EHelper::escapeHtmlStyleAttr($element['styles']['block']['borderStyle']) . ';
                     border-top-color: ' . EHelper::escapeHtmlStyleAttr($element['styles']['block']['borderColor']) . ';">
             </td>
            </tr>
          </table>
        </td>
      </tr>';
    return $template;
  }
}
