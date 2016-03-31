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
  static $line_height = 1.61803398875;
  static $padding_width = 20;

  static function getBlockStyles($element, $ignore_specific_styles = false) {
    if(!isset($element['styles']['block'])) {
      return;
    }
    return self::getStyles($element['styles'], 'block', $ignore_specific_styles);
  }

  static function getStyles($data, $type, $ignore_specific_styles = false) {
    $styles = array_map(function($attribute, $style) use ($ignore_specific_styles) {
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

  static function setStyle($style, $selector) {
    $css = $selector . '{' . PHP_EOL;
    foreach($style as $attribute => $individual_style) {
      $css .= self::translateCSSAttribute($attribute) . ':' . $individual_style . ';' .  PHP_EOL;
    }
    $css .= '}' . PHP_EOL;
    return $css;
  }
}