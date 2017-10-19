<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Logger;
use MailPoet\Newsletter\Renderer\Columns\ColumnsHelper;
use MailPoet\Newsletter\Renderer\StylesHelper;

class Image {
  static function render($element, $column_count) {
    if(empty($element['src'])) {
      return '';
    }
    if(substr($element['src'], 0, 1) == '/' && substr($element['src'], 1, 1) != '/') {
      $element['src'] = get_option('siteurl') . $element['src'];
    }

    $element['width'] = (int)$element['width'];
    $element['height'] = (int)$element['height'];
    $element = self::adjustImageDimensions($element, $column_count);
    $image_template = '
      <img style="max-width:' . $element['width'] . 'px;" src="' . $element['src'] . '"
      width="' . $element['width'] . '" alt="' . $element['alt'] . '"/>
      ';
    if(!empty($element['link'])) {
      $image_template = '<a href="' . $element['link'] . '">' . $image_template . '</a>';
    }
    $align = 'center';
    if(!empty($element['styles']['block']['textAlign']) && in_array($element['styles']['block']['textAlign'], array('left', 'right'))) {
      $align = $element['styles']['block']['textAlign'];
    }
    $template = '
      <tr>
        <td class="mailpoet_image ' . (($element['fullWidth'] === false) ? 'mailpoet_padded_bottom mailpoet_padded_side' : '') . '" align="' . $align . '" valign="top">
          ' . $image_template . '
        </td>
      </tr>';
    return $template;
  }

  static function adjustImageDimensions($element, $column_count) {
    $column_width = ColumnsHelper::columnWidth($column_count);
    $padded_width = StylesHelper::$padding_width * 2;
    // scale image to fit column width
    if($element['width'] > $column_width) {
      $ratio = $element['width'] / $column_width;
      $element['width'] = $column_width;
      $element['height'] = (int)ceil($element['height'] / $ratio);
    }
    // resize image if the image is padded and wider than padded column width
    if($element['fullWidth'] === false &&
      $element['width'] > ($column_width - $padded_width)
    ) {
      $ratio = $element['width'] / ($column_width - $padded_width);
      $element['width'] = $column_width - $padded_width;
      $element['height'] = (int)ceil($element['height'] / $ratio);
    }
    return $element;
  }
}
