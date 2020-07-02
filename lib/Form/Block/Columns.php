<?php

namespace MailPoet\Form\Block;

class Columns {
  public function render(array $block, string $content): string {
    return "<div {$this->getClass($block['params'] ?? [])}{$this->getStyles($block['params'] ?? [])}>$content</div>";
  }

  private function getStyles(array $params): string {
    $styles = [];
    if (isset($params['text_color'])) {
      $styles[] = "color:{$params['text_color']};";
    }
    if (isset($params['background_color']) && !isset($params['gradient'])) {
      $styles[] = "background-color:{$params['background_color']};";
    }
    if (isset($params['gradient'])) {
      $styles[] = "background:{$params['gradient']};";
    }
    if (count($styles)) {
      return ' style="' . implode('', $styles) . '"';
    }
    return '';
  }

  private function getClass(array $params): string {
    $classes = ['mailpoet_form_columns mailpoet_paragraph'];
    if (!empty($params['vertical_alignment'])) {
      $classes[] = "mailpoet_vertically_align_{$params['vertical_alignment']}";
    }
    if (!empty($params['background_color']) || !empty($params['gradient'])) {
      $classes[] = "mailpoet_column_with_background";
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
