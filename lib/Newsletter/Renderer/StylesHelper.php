<?php
namespace MailPoet\Newsletter\Renderer;

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
  static $line_height_multiplier = 1.6;
  static $heading_margin_multiplier = 0.3;
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
        return StylesHelper::translateCSSAttribute($attribute) . ': ' . $style . ' !important;';
      }
    }, array_keys($data[$type]), $data[$type]);
    return implode('', $styles);
  }

  static function translateCSSAttribute($attribute) {
    return (array_key_exists($attribute, self::$css_attributes)) ?
      self::$css_attributes[$attribute] :
      $attribute;
  }

  static function setStyle($style, $selector) {
    $css = $selector . '{' . PHP_EOL;
    $style = self::applyHeadingMargin($style, $selector);
    $style = self::applyLineHeight($style, $selector);
    foreach($style as $attribute => $individual_style) {
      $individual_style = self::applyFontFamily($attribute, $individual_style);
      $css .= self::translateCSSAttribute($attribute) . ':' . $individual_style . ';' . PHP_EOL;
    }
    $css .= '}' . PHP_EOL;
    return $css;
  }

  static function applyTextAlignment($block) {
    if(is_array($block)) {
      $text_alignment = isset($block['styles']['block']['textAlign']) ?
        strtolower($block['styles']['block']['textAlign']) :
        false;
      if(preg_match('/center|right|justify/i', $text_alignment)) {
        return $block;
      }
      $block['styles']['block']['textAlign'] = 'left';
      return $block;
    }
    return (preg_match('/text-align.*?[center|justify|right]/i', $block)) ?
      $block :
      $block . 'text-align:left;';
  }

  static function applyFontFamily($attribute, $style) {
    if($attribute !== 'fontFamily') return $style;
    return (isset(self::$font[$style])) ?
        self::$font[$style] :
        self::$font['Arial'];
  }

  static function applyHeadingMargin($style, $selector) {
    if(!preg_match('/h[1-4]/i', $selector)) return $style;
    $font_size = (int)$style['fontSize'];
    $style['margin'] = sprintf('0 0 %spx 0', self::$heading_margin_multiplier * $font_size);
    return $style;
  }

  static function applyLineHeight($style, $selector) {
    if(!preg_match('/mailpoet_paragraph|h[1-4]/i', $selector)) return $style;
    $font_size = (int)$style['fontSize'];
    $style['lineHeight'] = sprintf('%spx', self::$line_height_multiplier * $font_size);
    return $style;
  }
}