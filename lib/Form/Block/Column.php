<?php

namespace MailPoet\Form\Block;

class Column {
  public function render(array $block, string $content): string {
    return "<div {$this->getClass($block['params'])}{$this->getStyles($block['params'])}>$content</div>";
  }

  private function getStyles(array $params): string {
    if (isset($params['width'])) {
      return " style=\"flex-basis:{$params['width']}%;\"";
    }
    return '';
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
    return "class=\"$classes\"";
  }
}
