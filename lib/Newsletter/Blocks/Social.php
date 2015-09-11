<?php namespace MailPoet\Newsletter\Blocks;

class Social {

  static function render($element) {
    $iconsBlock = '';

    if(is_array($element['icons'])) {
      foreach ($element['icons'] as $icon) {
        $iconsBlock .= '
<a href="' . $icon['link'] . '">
  <img src="' . $icon['image'] . '" width = "32" height = "32"  style="width: 32px; height: 32px;" alt="' . $icon['iconType'] . '">
</a>
<img src="http://mp3.mailpoet.net/spacer.gif" width = "10" height = "1"  style="	width: 10px; height: 1px;">';
      }
    }

    $template = '
<tr>
  <td class="mailpoet_col mailpoet_social" valign="top">
    <div class="mailpoet_social-icon mailpoet_padded">' . $iconsBlock . ' </div>
  </td>
</tr>';

    return $template;
  }

}