<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;

class Social {
  static function render($element) {
    $icons_block = '';
    if (is_array($element['icons'])) {
      foreach ($element['icons'] as $index => $icon) {
        if (empty($icon['image'])) {
          continue;
        }

        $style = 'width:' . $icon['width'] . ';height:' . $icon['width'] . ';-ms-interpolation-mode:bicubic;border:0;display:inline;outline:none;';
        $icons_block .= '<a href="' . EHelper::escapeHtmlLinkAttr($icon['link']) . '" style="text-decoration:none!important;"
        ><img 
          src="' . EHelper::escapeHtmlLinkAttr($icon['image']) . '"
          width="' . (int)$icon['width'] . '" 
          height="' . (int)$icon['height'] . '" 
          style="' . EHelper::escapeHtmlStyleAttr($style) . '"
          alt="' . EHelper::escapeHtmlAttr($icon['iconType']) . '"
        ></a>&nbsp;';
      }
    }
    $alignment = isset($element['styles']['block']['textAlign']) ? $element['styles']['block']['textAlign'] : 'center';
    if (!empty($icons_block)) {
      $template = '
      <tr>
        <td class="mailpoet_padded_side mailpoet_padded_vertical" valign="top" align="' . EHelper::escapeHtmlAttr($alignment) . '">
          ' . $icons_block . '
        </td>
      </tr>';
      return $template;
    }
  }
}
