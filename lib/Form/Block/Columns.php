<?php

namespace MailPoet\Form\Block;

class Columns {
  public function render(array $block, string $content): string {
    return "<div {$this->getClass($block['params'] ?? [])}>$content</div>";
  }

  private function getClass(array $params): string {
    $classes = ['mailpoet_form_columns'];
    if (!empty($params['vertical_alignment'])) {
      $classes[] = "mailpoet_vertically_align_{$params['vertical_alignment']}";
    }
    if (!empty($params['background_color'])) {
      $classes[] = "has-{$params['background_color']}-background-color";
      $classes[] = "mailpoet_column_has_background";
    }
    if (!empty($params['text_color'])) {
      $classes[] = "has-{$params['text_color']}-color";
    }
    if (!empty($params['class_name'])) {
      $classes[] = $params['class_name'];
    }
    $classes = implode(' ', $classes);
    return "class=\"$classes\"";
  }
}
