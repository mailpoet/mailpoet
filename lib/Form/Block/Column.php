<?php

namespace MailPoet\Form\Block;

use MailPoet\WP\Functions as WPFunctions;

class Column {
  /** @var WPFunctions */
  private $wp;

  public function __construct(WPFunctions $wp) {
    $this->wp = $wp;
  }

  public function render(array $block, string $content): string {
    return "<div {$this->getClass($block['params'])}{$this->getStyles($block['params'])}>$content</div>";
  }

  private function getStyles(array $params): string {
    $styles = [];
    if (
      !empty($params['width']) &&
      (strlen($params['width']) > 0 && ctype_digit(substr($params['width'], 0, 1)))
    ) {
      $widthValue = $this->wp->escAttr($params['width']) . (is_numeric($params['width']) ? '%' : '');
      $styles[] = "flex-basis:{$widthValue}";
    }
    if (!empty($params['padding']) && is_array($params['padding'])) {
      $styles[] = $this->wp->escAttr(
        "padding:{$params['padding']['top']} {$params['padding']['right']} {$params['padding']['bottom']} {$params['padding']['left']}"
      );
    }
    if (!count($styles)) {
      return '';
    }
    return ' style="' . implode(';', $styles) . ';"';
  }

  private function getClass(array $params): string {
    $classes = ['mailpoet_form_column'];
    if (!empty($params['vertical_alignment'])) {
      $classes[] = "mailpoet_vertically_align_{$params['vertical_alignment']}";
    }
    if (!empty($params['class_name'])) {
      $classes[] = $params['class_name'];
    }
    $classes = implode(' ', $classes);
    return "class=\"{$this->wp->escAttr($classes)}\"";
  }
}
