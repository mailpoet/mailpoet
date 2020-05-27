<?php

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;
use MailPoet\Newsletter\Renderer\StylesHelper;

class Button {
  public function render($element, $columnBaseWidth) {
    $element['styles']['block']['width'] = $this->calculateWidth($element, $columnBaseWidth);
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

  public function calculateWidth($element, $columnBaseWidth) {
    $columnWidth = $columnBaseWidth - (StylesHelper::$paddingWidth * 2);
    $borderWidth = (int)$element['styles']['block']['borderWidth'];
    $buttonWidth = (int)$element['styles']['block']['width'];
    $buttonWidth = ($buttonWidth > $columnWidth) ?
      $columnWidth :
      $buttonWidth;
    $buttonWidth = $buttonWidth - (2 * $borderWidth) . 'px';
    return $buttonWidth;
  }
}
