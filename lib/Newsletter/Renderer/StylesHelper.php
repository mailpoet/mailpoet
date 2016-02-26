<?php
namespace MailPoet\Newsletter\Renderer;

use MailPoet\Newsletter\Renderer\Columns\ColumnsHelper;

class StylesHelper {
  static $css_attributes = array(
    'backgroundColor' => 'background-color',
    'fontColor' => 'color',
    'fontFamily' => 'font-family',
    'textDecoration' => 'text-decoration',
    'textAlign' => 'text-align',
    'fontSize' => 'font-size',
    'fontWeight' => 'font-weight',
    'borderWidth' => 'border-width',
    'borderStyle' => 'border-style',
    'borderColor' => 'border-color',
    'borderRadius' => 'border-radius',
    'lineHeight' => 'line-height'
  );
  static $font = array(
    'Arial' => "Arial, 'Helvetica Neue', Helvetica, sans-serif",
    'Comic Sans MS' => "'Comic Sans MS', 'Marker Felt-Thin', Arial, sans-serif",
    'Courier New' => "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace",
    'Georgia' => "Georgia, Times, 'Times New Roman', serif",
    'Lucida' => "'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
    'Tahoma' => 'Tahoma, Verdana, Segoe, sans-serif',
    'Times New Roman' => "'Times New Roman', Times, Baskerville, Georgia, serif",
    'Trebuchet MS' => "'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif",
    'Verdana' => 'Verdana, Geneva, sans-serif'
  );
  static $font_size = array(
    // font_size => array(columnCount => lineHeight);
    8 => array(
      1 => "20",
      2 => "15",
      3 => "13"
    ),
    9 => array(
      1 => "20",
      2 => "16",
      3 => "14"
    ),
    10 => array(
      1 => "20",
      2 => "17",
      3 => "15"
    ),
    11 => array(
      1 => "21",
      2 => "18",
      3 => "16"
    ),
    12 => array(
      1 => "22",
      2 => "19",
      3 => "17"
    ),
    13 => array(
      1 => "23",
      2 => "20",
      3 => "19"
    ),
    14 => array(
      1 => "24",
      2 => "21",
      3 => "20"
    ),
    15 => array(
      1 => "25",
      2 => "22",
      3 => "21"
    ),
    16 => array(
      1 => "26",
      2 => "23",
      3 => "22"
    ),
    17 => array(
      1 => "27",
      2 => "24",
      3 => "24"
    ),
    18 => array(
      1 => "28",
      2 => "25",
      3 => "25"
    ),
    19 => array(
      1 => "29",
      2 => "27",
      3 => "26"
    ),
    20 => array(
      1 => "30",
      2 => "28",
      3 => "27"
    ),
    21 => array(
      1 => "31",
      2 => "29",
      3 => "29"
    ),
    22 => array(
      1 => "32",
      2 => "30",
      3 => "30"
    ),
    23 => array(
      1 => "33",
      2 => "32",
      3 => "31"
    ),
    24 => array(
      1 => "34",
      2 => "33",
      3 => "32"
    ),
    25 => array(
      1 => "36",
      2 => "34",
      3 => "34"
    ),
    26 => array(
      1 => "37",
      2 => "35",
      3 => "35"
    ),
    27 => array(
      1 => "38",
      2 => "37",
      3 => "36"
    ),
    28 => array(
      1 => "39",
      2 => "38",
      3 => "37"
    ),
    29 => array(
      1 => "40",
      2 => "39",
      3 => "39"
    ),
    30 => array(
      1 => "42",
      2 => "40",
      3 => "40"
    ),
    31 => array(
      1 => "43",
      2 => "42",
      3 => "41"
    ),
    32 => array(
      1 => "44",
      2 => "43",
      3 => "43"
    ),
    33 => array(
      1 => "45",
      2 => "44",
      3 => "44"
    ),
    34 => array(
      1 => "47",
      2 => "46",
      3 => "45"
    ),
    35 => array(
      1 => "48",
      2 => "47",
      3 => "46"
    ),
    36 => array(
      1 => "49",
      2 => "48",
      3 => "48"
    ),
    37 => array(
      1 => "50",
      2 => "49",
      3 => "49"
    ),
    38 => array(
      1 => "52",
      2 => "51",
      3 => "50"
    ),
    39 => array(
      1 => "53",
      2 => "52",
      3 => "52"
    ),
    40 => array(
      1 => "54",
      2 => "53",
      3 => "53"
    )
  );
  static $padding_width = 20;

  static function getBlockStyles($element, $ignore_specific_styles = false) {
    if(!isset($element['styles']['block'])) {
      return;
    }
    return self::getStyles($element['styles'], 'block', $ignore_specific_styles);
  }

  static function getStyles($data, $type, $ignore_specific_styles = false) {
    $styles = array_map(function ($attribute, $style) use ($ignore_specific_styles) {
      if(!$ignore_specific_styles || !in_array($attribute, $ignore_specific_styles)) {
        return self::translateCSSAttribute($attribute) . ': ' . $style . ' !important;';
      }
    }, array_keys($data[$type]), $data[$type]);
    return implode('', $styles);
  }

  static function translateCSSAttribute($attribute) {
    return (array_key_exists($attribute, self::$css_attributes)) ?
      self::$css_attributes[$attribute] :
      $attribute;
  }

  static function setFontFamily($font_family, $selector) {
    $font_family = (isset(self::$font[$font_family])) ?
      self::$font[$font_family] :
      self::$font['Arial'];
    $css = $selector . '{' . PHP_EOL;
    $css .= 'font-family:' . $font_family . ';' . PHP_EOL;
    $css .= '}' . PHP_EOL;
    return $css;
  }

  static function setFontAndLineHeight($font_size, $selector) {
    $css = '';
    foreach(ColumnsHelper::columnClasses() as $column_count => $column_class) {
      $css .= '.mailpoet_content-' . $column_class . ' ' . $selector . '{' . PHP_EOL;
      $css .= 'font-size:' . $font_size . 'px;' . PHP_EOL;
      $css .= 'line-height:' . StylesHelper::$font_size[$font_size][$column_count] . 'px;' . PHP_EOL;
      $css .= '}' . PHP_EOL;
    }
    return $css;
  }

  static function setStyle($style, $selector) {
    $css = $selector . '{' . PHP_EOL;
    foreach($style as $attribute => $individual_style) {
      $css .= self::translateCSSAttribute($attribute) . ':' . $individual_style . ';' . PHP_EOL;
    }
    $css .= '}' . PHP_EOL;
    return $css;
  }
}