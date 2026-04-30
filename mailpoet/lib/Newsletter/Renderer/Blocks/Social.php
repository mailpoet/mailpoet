<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;

class Social {
  public function render($element) {
    $iconsBlock = '';
    if (is_array($element['icons'])) {
      foreach ($element['icons'] as $index => $icon) {
        if (!is_array($icon) || empty($icon['image'])) {
          continue;
        }

        // Width/height typically arrive as CSS strings like "32px"; PHP's lenient string-to-int strips the unit.
        $widthRaw = is_scalar($icon['width'] ?? null) ? (string)$icon['width'] : '';
        $heightRaw = is_scalar($icon['height'] ?? null) ? (string)$icon['height'] : '';
        $width = (int)$widthRaw;
        $height = (int)$heightRaw;
        $link = is_string($icon['link'] ?? null) ? $icon['link'] : '';
        $image = is_string($icon['image']) ? $icon['image'] : '';
        $iconType = is_string($icon['iconType'] ?? null) ? $icon['iconType'] : '';
        $style = 'width:' . $widthRaw . ';height:' . $heightRaw . ';-ms-interpolation-mode:bicubic;border:0;display:inline;outline:none;';
        $iconsBlock .= '<a href="' . EHelper::escapeHtmlLinkAttr($link) . '" style="text-decoration:none!important;"
        ><img
          src="' . EHelper::escapeHtmlLinkAttr($image) . '"
          width="' . $width . '"
          height="' . $height . '"
          style="' . EHelper::escapeHtmlStyleAttr($style) . '"
          alt="' . EHelper::escapeHtmlAttr($iconType) . '"
        ></a>&nbsp;';
      }
    }
    $alignment = isset($element['styles']['block']['textAlign']) ? $element['styles']['block']['textAlign'] : 'center';
    if (!empty($iconsBlock)) {
      $template = '
      <tr>
        <td class="mailpoet_padded_side mailpoet_padded_vertical" valign="top" align="' . EHelper::escapeHtmlAttr($alignment) . '">
          ' . $iconsBlock . '
        </td>
      </tr>';
      return $template;
    }
  }
}
