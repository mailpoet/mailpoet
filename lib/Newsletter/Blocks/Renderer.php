<?php namespace MailPoet\Newsletter\Blocks;

class Renderer {

  public $typeFace = array(
    'Arial' => "Arial, 'Helvetica Neue', Helvetica, sans-serif",
    'Comic Sans MS' => "'Comic Sans MS', 'Marker Felt-Thin', Arial, sans-serif",
    'Courier New' => "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace",
    'Georgia' => "Georgia, Times, 'Times New Roman', serif",
    'Lucida' => "'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
    'Tahoma' => "Tahoma, Verdana, Segoe, sans-serif",
    'Times New Roman' => "'Times New Roman', Times, Baskerville, Georgia, serif",
    'Trebuchet MS' => "'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif",
    'Verdana' => "Verdana, Geneva, sans-serif"
  );

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

  function render($data, $column = null) {
    array_map(function($block) use(&$blockContent, &$columns) {
      $blockContent .= $this->createElementFromBlockType($block);
      if(isset($block['blocks'])) {
        $blockContent = $this->render($block);
      }
      // vertical orientation denotes column container
      if($block['type'] === 'container' && $block['orientation'] === 'vertical') {
        $columns[] = $blockContent;
      }
    }, $data['blocks']);

    return (isset($columns)) ? $columns : $blockContent;
  }

  function createElementFromBlockType($block) {
    $blockClass = __NAMESPACE__ . '\\' . ucfirst($block['type']);
    return (class_exists($blockClass)) ? $blockClass::render($block) : '';
  }

  function getBlockStyles($element, $ignore = false) {
    if(!isset($element['styles']['block'])) {
      return;
    }

    return $this->getStyles($element['styles'], 'block', $ignore);
  }

  function getStyles($data, $type, $ignore = false) {
    array_map(function($attribute, $style) use(&$styles, $ignore) {
      if(!$ignore || !in_array($attribute, $ignore)) {
        $styles .= $this->translateCSSAttribute($attribute) . ': ' . $style . ' !important;';
      }
    }, array_keys($data[$type]), $data[$type]);

    return $styles;
  }

  function translateCSSAttribute($attribute) {
    return (isset($this->cssAtributesTable[$attribute])) ? $this->cssAtributesTable[$attribute] : $attribute;
  }
}
