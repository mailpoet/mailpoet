<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

class Social {
  static function render($element) {
    $icons_block = '';
    if(is_array($element['icons'])) {
      foreach($element['icons'] as $index => $icon) {
        if(empty($icon['image'])) {
          continue;
        }

        $icons_block .= '
        <a href="' . $icon['link'] . '" style="text-decoration:none!important;">
          <img src="' . $icon['image'] . '" width="' . (int)$icon['width'] . '" height="' . (int)$icon['height'] . '" style="width:' . $icon['width'] . ';height:' . $icon['width'] . ';-ms-interpolation-mode:bicubic;border:0;display:inline;outline:none;" alt="' . $icon['iconType'] . '">
        </a>';
      }
    }
    if(!empty($icons_block)) {
      $template = '
      <tr>
        <td class="mailpoet_padded_side mailpoet_padded_bottom" valign="top" align="center">
          ' . $icons_block . '
        </td>
      </tr>';
      return $template;
    }
  }
}
