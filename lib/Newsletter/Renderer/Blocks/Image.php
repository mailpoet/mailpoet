<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\Columns\ColumnsHelper;
use MailPoet\Newsletter\Renderer\StylesHelper;

class Image {
  static function render($element, $columnCount) {
    $element = self::getImageDimensions($element, $columnCount);
    $template = '
      <tr>
        <td class="mailpoet_image ' . $element['paddedClass'] . '" align="center" valign="top">
          <img style="max-width:' . $element['width'] . 'px;" src="' . $element['src'] . '"
          width="' . $element['width'] . '" height="' . $element['height'] . '" alt="' . $element['alt'] . '"/>
          ' . json_encode($element) . '
        </td>
      </tr>';
    return $template;
  }

  static function getImageDimensions($element, $column_count) {
    $column_width = ColumnsHelper::columnWidth($column_count);
    $padded_width = StylesHelper::$padding_width * 2;
    // resize image if it's wider than the column width
    if((int) $element['width'] >= $column_width) {
      $ratio = (int) $element['width'] / $column_width;
      $element['width'] = $column_width;
      $element['height'] = ceil((int) $element['height'] / $ratio);
    }
    if($element['fullWidth'] == false && $element['width'] >= $column_width) {
      // resize image if the padded option is on
      $ratio = (int) $element['width'] / ((int) $element['width'] - $padded_width);
      $element['width'] = (int) $element['width'] - $padded_width;
      $element['height'] = ceil((int) $element['height'] / $ratio);
      $element['paddedClass'] = 'mailpoet_padded';
    } else {
      $element['width'] = (int) $element['width'];
      $element['height'] = (int) $element['height'];
      $element['paddedClass'] = '';
    }
    return $element;
  }
}
