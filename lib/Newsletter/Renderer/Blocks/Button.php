<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;
use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;

class Button {
  static function render($element, $column_base_width) {
    $element['styles']['block']['width'] = self::calculateWidth($element, $column_base_width);
    $styles = 'display:inline-block;-webkit-text-size-adjust:none;mso-hide:all;text-decoration:none !important;text-align:center;' . StylesHelper::getBlockStyles($element, $exclude = ['textAlign']);
    $styles = EHelper::escapeHtmlStyleAttr($styles);
    $template = '
      <tr>
        <td class="mailpoet_padded_vertical mailpoet_padded_side" valign="top">
          <div>
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;">
              <tr>
                <td class="mailpoet_button-container" style="text-align:' . $element['styles']['block']['textAlign'] . ';"><!--[if mso]>
                  <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
                    href="' . EHelper::escapeHtmlLinkAttr($element['url']) . '"
                    style="height:' . EHelper::escapeHtmlStyleAttr($element['styles']['block']['lineHeight']) . ';
                           width:' . EHelper::escapeHtmlStyleAttr($element['styles']['block']['width']) . ';
                           v-text-anchor:middle;"
                    arcsize="' . round((int)$element['styles']['block']['borderRadius'] / (int)$element['styles']['block']['lineHeight'] * 100) . '%"
                    strokeweight="' . EHelper::escapeHtmlAttr($element['styles']['block']['borderWidth']) . '"
                    strokecolor="' . EHelper::escapeHtmlAttr($element['styles']['block']['borderColor']) . '"
                    fillcolor="' . EHelper::escapeHtmlAttr($element['styles']['block']['backgroundColor']) . '">
                  <w:anchorlock/>
                  <center style="color:' . EHelper::escapeHtmlStyleAttr($element['styles']['block']['fontColor']) . ';
                    font-family:' . EHelper::escapeHtmlStyleAttr($element['styles']['block']['fontFamily']) . ';
                    font-size:' . EHelper::escapeHtmlStyleAttr($element['styles']['block']['fontSize']) . ';
                    font-weight:bold;">' . EHelper::escapeHtmlText($element['text']) . '
                  </center>
                  </v:roundrect>
                  <![endif]--><a class="mailpoet_button" href="' . EHelper::escapeHtmlLinkAttr($element['url']) . '" style="' . $styles . '"> ' . EHelper::escapeHtmlText($element['text']) . '</a>
                </td>
              </tr>
            </table>
          </div>
        </td>
      </tr>';
    return $template;
  }

  static function calculateWidth($element, $column_base_width) {
    $column_width = $column_base_width - (StylesHelper::$padding_width * 2);
    $border_width = (int)$element['styles']['block']['borderWidth'];
    $button_width = (int)$element['styles']['block']['width'];
    $button_width = ($button_width > $column_width) ?
      $column_width :
      $button_width;
    $button_width = $button_width - (2 * $border_width) . 'px';
    return $button_width;
  }
}
