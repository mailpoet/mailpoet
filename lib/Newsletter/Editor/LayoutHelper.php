<?php
namespace MailPoet\Newsletter\Editor;

class LayoutHelper {
  static function row($blocks) {
    return array(
      'type' => 'container',
      'orientation' => 'horizontal',
      'styles' => array(
        'block' => array(
          'backgroundColor' => 'transparent'
        )
      ),
      'blocks' => $blocks
    );
  }

  static function col($blocks) {
    return array(
      'type' => 'container',
      'orientation' => 'vertical',
      'styles' => array(
        'block' => array(
          'backgroundColor' => 'transparent'
        )
      ),
      'blocks' => $blocks
    );
  }
}