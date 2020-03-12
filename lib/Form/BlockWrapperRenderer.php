<?php

namespace MailPoet\Form;

class BlockWrapperRenderer {
  public function render(array $block, string $blockContent): string {
    $classes = isset($block['params']['class_name']) ? " " . $block['params']['class_name'] : '';
    return '<div class="mailpoet_paragraph' . $classes . '">' . $blockContent . '</div>';
  }
}
