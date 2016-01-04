<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

class Social {
  static function render($element) {
    $iconsBlock = '';
    if (is_array($element['icons'])) {
      foreach ($element['icons'] as $index => $icon) {
        $iconsBlock .= '
        <a href="' . $icon['link'] . '" style="text-decoration:none!important;">
          <img src="' . $icon['image'] . '" width="' . (int) $icon['width'] . '" height="' . (int) $icon['height'] . '" style="width:' . $icon['width'] . ';height:' . $icon['width'] . ';-ms-interpolation-mode:bicubic;border:0;display:inline;outline:none;" alt="' . $icon['iconType'] . '">
        </a>';
      }
      $template = '
      <tr>
        <td class="mailpoet_padded" valign="top" align="center">
          ' . $iconsBlock . '
        </td>
      </tr>';
      return $template;
    }
  }
}