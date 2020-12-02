<?php

namespace MailPoet\Form\Block;

class Divider {
  const DEFAULT_ATTRIBUTES = [
    'height' => 1,
    'type' => 'divider',
    'style' => 'solid',
    'dividerHeight' => 1,
    'dividerWidth' => 100,
    'color' => 'black',
  ];

  public function render($block): string {
    $classes = ['mailpoet_spacer'];
    if (isset($block['params']['type']) && $block['params']['type'] === 'divider') {
      $classes[] = 'mailpoet_has_divider';
    }
    if (!empty($block['params']['class_name'])) {
      $classes[] = $block['params']['class_name'];
    }
    $classAttr = join(' ', $classes);
    $height = $block['params']['height'] ?? self::DEFAULT_ATTRIBUTES['height'];
    return "<div class='{$classAttr}' style='height: {$height}px;'>"
    . $this->renderDivider($block)
    . '</div>';
  }

  private function renderDivider(array $block): string {
    if (isset($block['params']['type']) && $block['params']['type'] === 'spacer') {
      return '';
    }
    $width = $block['params']['divider_width'] ?? self::DEFAULT_ATTRIBUTES['dividerWidth'];
    $style = $block['params']['style'] ?? self::DEFAULT_ATTRIBUTES['style'];
    $dividerHeight = $block['params']['divider_height'] ?? self::DEFAULT_ATTRIBUTES['dividerHeight'];
    $color = $block['params']['color'] ?? self::DEFAULT_ATTRIBUTES['color'];

    $dividerStyles = [
      "border-top-style: $style",
      "border-top-width: {$dividerHeight}px",
      "border-top-color: $color",
      "height: {$dividerHeight}px",
      "width: $width%",
    ];
    $style = implode(";", $dividerStyles);
    return "<div class='mailpoet_divider' data-automation-id='form_divider' style='$style'></div>";
  }
}
