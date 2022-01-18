<?php

namespace MailPoet\Newsletter\Editor;

class LayoutHelper {
  public static function row($blocks, $styles = []) {
    if (empty($styles['backgroundColor'])) {
      $styles['backgroundColor'] = 'transparent';
    }
    return [
      'type' => 'container',
      'orientation' => 'horizontal',
      'styles' => ['block' => $styles],
      'blocks' => $blocks,
    ];
  }

  public static function col($blocks, $styles = []) {
    if (empty($styles['backgroundColor'])) {
      $styles['backgroundColor'] = 'transparent';
    }
    return [
      'type' => 'container',
      'orientation' => 'vertical',
      'styles' => ['block' => $styles],
      'blocks' => $blocks,
    ];
  }
}
