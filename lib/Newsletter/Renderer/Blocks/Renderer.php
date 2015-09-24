<?php namespace MailPoet\Newsletter\Renderer\Blocks;

class Renderer {
  function render($data) {
    array_map(function ($block) use (&$blockContent, &$columns) {
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

}
