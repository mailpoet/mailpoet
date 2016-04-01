<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;

class Renderer {
  function render($data, $column_count) {
    $block_content = '';
    array_map(function($block) use (&$block_content, &$columns, $column_count) {
      $block_content .= $this->createElementFromBlockType($block, $column_count);
      if(isset($block['blocks'])) {
        $block_content = $this->render($block, $column_count);
      }
      // vertical orientation denotes column container
      if($block['type'] === 'container' && $block['orientation'] === 'vertical') {
        $columns[] = $block_content;
      }
    }, $data['blocks']);
    return (isset($columns)) ? $columns : $block_content;
  }

  function createElementFromBlockType($block, $column_count) {
    $block = StylesHelper::setTextAlign($block);
    $block_class = __NAMESPACE__ . '\\' . ucfirst($block['type']);
    return (class_exists($block_class)) ? $block_class::render($block, $column_count) : '';
  }
}