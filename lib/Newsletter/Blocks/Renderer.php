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
    $blockContent = '';
    $blockCount = count($data['blocks']);

    foreach ($data['blocks'] as $i => $block) {
      $blockContent .= $this->createElementFromBlockType($block);
      if(isset($block['blocks']) && is_array($block['blocks'])) {
        $blockContent = $this->render($block);
      }

      // vertical orientation denotes column container
      if($block['type'] == 'container' && $block['orientation'] == 'vertical') {
        $columns[] = $blockContent;
      }
    }

    return (isset($columns)) ? $columns : $blockContent;
  }

  function createElementFromBlockType($block) {
    switch ($block['type']) {
    case 'header':
      $element = Header::render($block);
    break;
    case 'image':
      $element = Image::render($block);
    break;
    case 'text':
      $element = Text::render($block);
    break;
    case 'button':
      $element = Button::render($block);
    break;
    case 'divider':
      $element = Divider::render($block);
    break;
    case 'spacer':
      $element = Spacer::render($block);
    break;
    case 'social':
      $element = Social::render($block);
    break;
    case 'footer':
      $element = Footer::render($block);
    break;
    default:
      $element = '';//'UNRECOGNIZED ELEMENT';
    break;
    }

    return $element;
  }

  function getBlockStyles($element, $ignore = false) {
    if(!isset($element['styles']['block'])) {
      return;
    }

    return $this->getStyles($element['styles'], 'block', $ignore);
  }

  function getStyles($styles, $type, $ignore = false) {
    $css = '';
    foreach ($styles[$type] as $attribute => $style) {
      if($ignore && in_array($attribute, $ignore)) {
        continue;
      }
      $css .= $this->translateCSSAttribute($attribute) . ': ' . $style . ' !important;';
    }

    return $css;
  }

  function translateCSSAttribute($attribute) {
    return (isset($this->cssAtributesTable[$attribute])) ? $this->cssAtributesTable[$attribute] : $attribute;
  }
}
