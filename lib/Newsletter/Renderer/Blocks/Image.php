<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;

class Image {
  static function render($element, $column_base_width) {
    if (empty($element['src'])) {
      return '';
    }
    if (substr($element['src'], 0, 1) == '/' && substr($element['src'], 1, 1) != '/') {
      $element['src'] = WPFunctions::get()->getOption('siteurl') . $element['src'];
    }

    $element['width'] = str_replace('px', '', $element['width']);
    $element['height'] = str_replace('px', '', $element['height']);
    $original_width = 0;
    if (is_numeric($element['width']) && is_numeric($element['height'])) {
      $element['width'] = (int)$element['width'];
      $element['height'] = (int)$element['height'];
      $original_width = $element['width'];
      $element = self::adjustImageDimensions($element, $column_base_width);
    }

    // If image was downsized because of column width set width to aways fill full column (e.g. on mobile)
    $style = '';
    if ($element['fullWidth'] === true && $original_width > $element['width']) {
      $style = 'style="width:100%"';
    }

    $image_template = '
      <img src="' . EHelper::escapeHtmlLinkAttr($element['src']) . '" width="' . EHelper::escapeHtmlAttr($element['width']) . '" alt="' . EHelper::escapeHtmlAttr($element['alt']) . '"' . $style . '/>
      ';
    if (!empty($element['link'])) {
      $image_template = '<a href="' . EHelper::escapeHtmlLinkAttr($element['link']) . '">' . trim($image_template) . '</a>';
    }
    $align = 'center';
    if (!empty($element['styles']['block']['textAlign']) && in_array($element['styles']['block']['textAlign'], ['left', 'right'])) {
      $align = $element['styles']['block']['textAlign'];
    }

    $template = '
      <tr>
        <td class="mailpoet_image ' . (($element['fullWidth'] === false) ? 'mailpoet_padded_vertical mailpoet_padded_side' : '') . '" align="' . EHelper::escapeHtmlAttr($align) . '" valign="top">
          ' . trim($image_template) . '
        </td>
      </tr>';
    return $template;
  }

  static function adjustImageDimensions($element, $column_base_width) {
    $padded_width = StylesHelper::$padding_width * 2;
    // scale image to fit column width
    if ($element['width'] > $column_base_width) {
      $ratio = $element['width'] / $column_base_width;
      $element['width'] = $column_base_width;
      $element['height'] = (int)ceil($element['height'] / $ratio);
    }
    // resize image if the image is padded and wider than padded column width
    if ($element['fullWidth'] === false &&
      $element['width'] > ($column_base_width - $padded_width)
    ) {
      $ratio = $element['width'] / ($column_base_width - $padded_width);
      $element['width'] = $column_base_width - $padded_width;
      $element['height'] = (int)ceil($element['height'] / $ratio);
    }
    return $element;
  }
}
