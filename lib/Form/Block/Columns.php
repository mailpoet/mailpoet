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
    $classes = implode(' ', $classes);
    return "class=\"$classes\"";
  }
}
