<?php

namespace MailPoet\Form\Block;

class Image {
  public function render(array $block): string {
    if (empty($block['params']['url'])) {
      return '';
    }
    return $this->wrapImage($block['params'], $this->renderImage($block['params']));
  }

  private function renderImage(array $params) {
    $attributes = [];
    $attributes[] = 'src="' . $params['url'] . '"';
    $attributes[] = $params['alt'] ? 'alt="' . $params['alt'] . '"' : 'alt';
    if ($params['title']) {
      $attributes[] = 'title="' . $params['title'] . '"';
    }
    if ($params['id']) {
      // WordPress automatically renders srcset based on this class
      $attributes[] = 'class="wp-image-' . $params['id'] . '"';
    }
    if ($params['width']) {
      $attributes[] = 'width="' . intval($params['width']) . '"';
    }
    if ($params['height']) {
      $attributes[] = 'height="' . intval($params['height']) . '"';
    }
    return '<img ' . implode(' ', $attributes) . '" />';
  }

  private function wrapImage(array $params, string $img) {
    // Figure
    $figureClasses = ['size-' . $params['size_slug']];
    if ($params['align']) {
      $figureClasses[] = 'align' . $params['align'];
    }
    $caption = $params['caption'] ? "<figcaption>{$params['caption']}</figcaption>" : '';
    $figure = '<figure class="' . implode(' ', $figureClasses) . '">' . $img . $caption . '</figure>';
    // Main wrapper
    $divClasses = ['mailpoet_form_image'];
    if (trim($params['class_name'])) {
      $divClasses[] = trim($params['class_name']);
    }
    return '<div class="' . implode(' ', $divClasses) . '">' . $figure . '</div>';
  }
}
