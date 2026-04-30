<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;

class Social {
  public function render($element) {
    $iconsBlock = '';
    if (is_array($element['icons'])) {
      foreach ($element['icons'] as $index => $icon) {
        if (empty($icon['image'])) {
          continue;
        }

        // Width/height typically arrive as CSS strings like "32px"; PHP's lenient string-to-int strips the unit.
        $width = is_scalar($icon['width']) ? (int)$icon['width'] : 0;
        $height = is_scalar($icon['height']) ? (int)$icon['height'] : 0;
        $style = 'width:' . $icon['width'] . ';height:' . $icon['height'] . ';-ms-interpolation-mode:bicubic;border:0;display:inline;outline:none;';
        $iconsBlock .= '<a href="' . EHelper::escapeHtmlLinkAttr($icon['link']) . '" style="text-decoration:none!important;"
        ><img
          src="' . EHelper::escapeHtmlLinkAttr($icon['image']) . '"
          width="' . $width . '"
          height="' . $height . '"
          style="' . EHelper::escapeHtmlStyleAttr($style) . '"
          alt="' . EHelper::escapeHtmlAttr($icon['iconType']) . '"
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
