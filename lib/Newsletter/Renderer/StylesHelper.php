<?php
namespace MailPoet\Newsletter\Renderer;

class StylesHelper {
  static $css_attributes = [
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
    'lineHeight' => 'line-height',
  ];
  static $font = [
    'Arial' => "Arial, 'Helvetica Neue', Helvetica, sans-serif",
    'Comic Sans MS' => "'Comic Sans MS', 'Marker Felt-Thin', Arial, sans-serif",
    'Courier New' => "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace",
    'Georgia' => "Georgia, Times, 'Times New Roman', serif",
    'Lucida' => "'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
    'Tahoma' => 'Tahoma, Verdana, Segoe, sans-serif',
    'Times New Roman' => "'Times New Roman', Times, Baskerville, Georgia, serif",
    'Trebuchet MS' => "'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif",
    'Verdana' => 'Verdana, Geneva, sans-serif',
    'Arvo' => 'arvo, courier, georgia, serif',
    'Lato' => "lato, 'helvetica neue', helvetica, arial, sans-serif",
    'Lora' => "lora, georgia, 'times new roman', serif",
    'Merriweather' => "merriweather, georgia, 'times new roman', serif",
    'Merriweather Sans' => "'merriweather sans', 'helvetica neue', helvetica, arial, sans-serif",
    'Noticia Text' => "'noticia text', georgia, 'times new roman', serif",
    'Open Sans' => "'open sans', 'helvetica neue', helvetica, arial, sans-serif",
    'Playfair Display' => "'playfair display', georgia, 'times new roman', serif",
    'Roboto' => "roboto, 'helvetica neue', helvetica, arial, sans-serif",
    'Source Sans Pro' => "'source sans pro', 'helvetica neue', helvetica, arial, sans-serif",
    'Oswald' => "Oswald, 'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif",
    'Raleway' => "Raleway, 'Century Gothic', CenturyGothic, AppleGothic, sans-serif",
    'Permanent Marker' => "'Permanent Marker', Tahoma, Verdana, Segoe, sans-serif",
    'Pacifico' => "Pacifico, 'Arial Narrow', Arial, sans-serif",
  ];
  static $custom_fonts = [
    'Arvo',
    'Lato',
    'Lora',
    'Merriweather',
    'Merriweather Sans',
    'Noticia Text',
    'Open Sans',
    'Playfair Display',
    'Roboto',
    'Source Sans Pro',
    'Oswald',
    'Raleway',
    'Permanent Marker',
    'Pacifico',
  ];
  static $default_line_height = 1.6;
  static $heading_margin_multiplier = 0.3;
  static $padding_width = 20;

  static function getBlockStyles($element, $ignore_specific_styles = false) {
    if (!isset($element['styles']['block'])) {
      return;
    }
    return self::getStyles($element['styles'], 'block', $ignore_specific_styles);
  }

  static function getStyles($data, $type, $ignore_specific_styles = false) {
    $styles = array_map(function($attribute, $style) use ($ignore_specific_styles) {
      if (!$ignore_specific_styles || !in_array($attribute, $ignore_specific_styles)) {
        $style = StylesHelper::applyFontFamily($attribute, $style);
        return StylesHelper::translateCSSAttribute($attribute) . ': ' . $style . ';';
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
    foreach ($style as $attribute => $individual_style) {
      $individual_style = self::applyFontFamily($attribute, $individual_style);
      $css .= self::translateCSSAttribute($attribute) . ':' . $individual_style . ';' . PHP_EOL;
    }
    $css .= '}' . PHP_EOL;
    return $css;
  }

  static function applyTextAlignment($block) {
    if (is_array($block)) {
      $text_alignment = isset($block['styles']['block']['textAlign']) ?
        strtolower($block['styles']['block']['textAlign']) :
        false;
      if (preg_match('/center|right|justify/i', $text_alignment)) {
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
    if ($attribute !== 'fontFamily') return $style;
    return (isset(self::$font[$style])) ?
        self::$font[$style] :
        self::$font['Arial'];
  }

  static function applyHeadingMargin($style, $selector) {
    if (!preg_match('/h[1-4]/i', $selector)) return $style;
    $font_size = (int)$style['fontSize'];
    $style['margin'] = sprintf('0 0 %spx 0', self::$heading_margin_multiplier * $font_size);
    return $style;
  }

  static function applyLineHeight($style, $selector) {
    if (!preg_match('/mailpoet_paragraph|h[1-4]/i', $selector)) return $style;
    $line_height = isset($style['lineHeight']) ? (float)$style['lineHeight'] : self::$default_line_height;
    $font_size = (int)$style['fontSize'];
    $style['lineHeight'] = sprintf('%spx', $line_height * $font_size);
    return $style;
  }

  private static function getCustomFontsNames($styles) {
    $font_names = [];
    foreach ($styles as $style) {
      if (isset($style['fontFamily']) && in_array($style['fontFamily'], self::$custom_fonts)) {
        $font_names[$style['fontFamily']] = true;
      }
    }
    return array_keys($font_names);
  }

  static function getCustomFontsLinks($styles) {
    $links = [];
    foreach (self::getCustomFontsNames($styles) as $name) {
      $links[] = urlencode($name) . ':400,400i,700,700i';
    }
    if (!count($links)) {
      return '';
    }
    return '<!--[if !mso]><link href="https://fonts.googleapis.com/css?family='
      . implode("|", $links)
      . '" rel="stylesheet"><![endif]-->';
  }
}
