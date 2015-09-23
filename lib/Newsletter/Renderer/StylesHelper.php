<?php namespace MailPoet\Newsletter\Renderer;

class StylesHelper {
  public $cssAtributesTable = array(
    'backgroundColor' => 'background-color',
    'fontColor' => 'color',
    'fontFamily' => 'font-family',
    'textDecoration' => 'text-decoration',
    'textAlign' => 'text-align',
    'fontSize' => 'font-size',
    'borderWidth' => 'border-width',
    'borderStyle' => 'border-style',
    'borderColor' => 'border-color',
    'borderRadius' => 'border-radius',
    'lineHeight' => 'line-height'
  );

  function getBlockStyles($element) {
    if(!isset($element['styles']['block'])) {
      return;
    }

    return $this->getStyles($element['styles'], 'block');
  }

  function getStyles($data, $type) {
    $styles = array_map(function ($attribute, $style)  {
        return $this->translateCSSAttribute($attribute) . ': ' . $style . ' !important;';
    }, array_keys($data[$type]), $data[$type]);

    return implode('', $styles);
  }

  function translateCSSAttribute($attribute) {
    return (array_key_exists($attribute, $this->cssAtributesTable)) ? $this->cssAtributesTable[$attribute] : $attribute;
  }
}
