<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

class Renderer {
  function render($data, $columnCount) {
    array_map(function ($block) use (&$blockContent, &$columns, $columnCount) {
      $blockContent .= $this->createElementFromBlockType($block, $columnCount);
      if(isset($block['blocks'])) {
        $blockContent = $this->render($block, $columnCount);
      }
      // vertical orientation denotes column container
      if($block['type'] === 'container' && $block['orientation'] === 'vertical') {
        $columns[] = $blockContent;
      }
    }, $data['blocks']);

    return (isset($columns)) ? $columns : $blockContent;
  }

  function createElementFromBlockType($block, $columnCount) {
    $blockClass = __NAMESPACE__ . '\\' . ucfirst($block['type']);
    return (class_exists($blockClass)) ? $blockClass::render($block, $columnCount) : '';
  }

}
