<?php

namespace MailPoet\Form\Block;

class Heading {
  public function render(array $block): string {
    $content = ($block['params']['content'] ?? '');
    return $this->wrapContent($content, $block);
  }

  private function wrapContent(string $content, array $block): string {
    $tag = $this->renderTag($block);
    $attributes = $this->renderAttributes($block);
    $openTag = $this->getOpenTag($tag, $attributes);
    return $openTag
      . $content
      . "</$tag>";
  }

  private function renderTag(array $block): string {
    $tag = 'h2';
    if (isset($block['params']['level'])) {
      $tag = 'h' . $block['params']['level'];
    }
    return $tag;
  }

  private function renderAttributes(array $block): array {
    $result = [];
    if (isset($block['params']['class_name'])) {
      $result[] = $this->renderClass($block);
    }
    if (isset($block['params']['anchor'])) {
      $result[] = $this->renderAnchor($block);
    }
    if (isset($block['params']['align']) || isset($block['params']['text_color'])) {
      $result[] = $this->renderStyle($block);
    }
    return $result;
  }

  private function getOpenTag(string $tag, array $attributes): string {
    if (empty($attributes)) {
      return "<$tag>";
    }
    return "<$tag " . join(' ', $attributes) . ">";
  }

  private function renderClass(array $block): string {
    return 'class="'
      . $block['params']['class_name']
      . '"';
  }

  private function renderAnchor(array $block): string {
    return 'id="'
      . $block['params']['anchor']
      . '"';
  }

  private function renderStyle(array $block): string {
    $styles = [];
    if (isset($block['params']['align'])) {
      $styles[] = 'text-align: ' . $block['params']['align'];
    }
    if (isset($block['params']['text_color'])) {
      $styles[] = 'color: ' . $block['params']['text_color'];
    }
    return 'style="'
      . join('; ', $styles)
      . '"';
  }
}
