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
        if ($index !== count($element['icons']) - 1) $iconsBlock .= '<img src="https://upload.wikimedia.org/wikipedia/commons/5/52/Spacer.gif" width="10" height="1" style="width:10px;height:1px;-ms-interpolation-mode:bicubic;border:0;display:inline;outline:none;" />';
      }
      $template = '
      <tr>
        <td class="mailpoet_padded" valign="top" align="center">
          <div class="mailpoet_social-icon" style="word-break:break-word;word-wrap:break-word;">
          ' . $iconsBlock . '
          </div>
        </td>
      </tr>';
      return $template;
    }
  }
}