<?php
namespace MailPoet\Newsletter\Editor;

class LayoutHelper {
  static function row($blocks) {
    return array(
      'type' => 'container',
      'orientation' => 'horizontal',
      'blocks' => $blocks
    );
  }

  static function col($blocks) {
    return array(
      'type' => 'container',
      'orientation' => 'vertical',
      'blocks' => $blocks
    );
  }
}