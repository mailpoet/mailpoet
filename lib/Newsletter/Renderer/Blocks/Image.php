<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\Columns\ColumnsHelper;
use MailPoet\Newsletter\Renderer\StylesHelper;

class Image {
  static function render($element, $columnCount) {
    $element['width'] = (int) $element['width'];
    $element['height'] = (int) $element['height'];
    $element = self::adjustImageDimensions($element, $columnCount);
    $template = '
      <tr>
        <td class="mailpoet_image ' . (($element['fullWidth'] === false) ? 'mailpoet_padded' : '') . '" align="center" valign="top">
          <img style="max-width:' . $element['width'] . 'px;" src="' . $element['src'] . '"
          width="' . $element['width'] . '" height="' . $element['height'] . '" alt="' . $element['alt'] . '"/>
        </td>
      </tr>';
    return $template;
  }

  static function adjustImageDimensions($element, $column_count) {
    $column_width = ColumnsHelper::columnWidth($column_count);
    $padded_width = StylesHelper::$padding_width * 2;
    // scale image to fit column width
    if($element['width'] > $column_width ||
      ($element['width'] < $column_width && $element['fullWidth'] === true)
    ) {
      $ratio = $element['width'] / $column_width;
      $element['width'] = $column_width;
      $element['height'] = ceil($element['height'] / $ratio);
    }
    // resize image if the image is padded and wider than column width
    if($element['fullWidth'] === false && $element['width'] >= $column_width) {
      $ratio = $element['width'] / ($element['width'] - $padded_width);
      $element['width'] = $element['width'] - $padded_width;
      $element['height'] = ceil($element['height'] / $ratio);
    }
    return $element;
  }
}