<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form\Block;

use MailPoet\Form\FormHtmlSanitizer;
use MailPoet\WP\Functions as WPFunctions;

class Image {
  /** @var WPFunctions */
  private $wp;

  /** @var FormHtmlSanitizer */
  private $htmlSanitizer;

  public function __construct(
    WPFunctions $wp,
    FormHtmlSanitizer $htmlSanitizer
  ) {
    $this->wp = $wp;
    $this->htmlSanitizer = $htmlSanitizer;
  }

  public function render(array $block): string {
    if (empty($block['params']['url'])) {
      return '';
    }
    return $this->wrapImage($block['params'], $this->renderImage($block['params']));
  }

  private function renderImage(array $params): string {
    $attributes = [];
    $styles = [];
    $attributes[] = 'src="' . $this->wp->escUrl($params['url']) . '"';
    $attributes[] = $params['alt'] ? 'alt="' . $this->wp->escAttr($params['alt']) . '"' : 'alt';
    if ($params['title']) {
      $attributes[] = 'title="' . $this->wp->escAttr($params['title']) . '"';
    }
    if ($params['id']) {
      $attributes[] = 'class="wp-image-' . $this->wp->escAttr($params['id']) . '"';
      $attributes[] = 'srcset="' . $this->wp->wpGetAttachmentImageSrcset(intval($params['id']), $params['size_slug']) . '"';
    }
    // Sizes are pixels with or without the unit; the editor saves "auto" for the side it scales by aspect ratio
    $width = intval($params['width']);
    if ($width > 0) {
      $attributes[] = 'width=' . $width;
      $styles[] = 'width: ' . $width . 'px';
    }
    $height = intval($params['height']);
    if ($height > 0) {
      $attributes[] = 'height=' . $height;
      $styles[] = 'height: ' . $height . 'px';
    }
    if ($styles) {
      $attributes[] = 'style="' . $this->wp->escAttr(implode(';', $styles)) . '"';
    }
    return '<img ' . implode(' ', $attributes) . '>';
  }

  private function wrapImage(array $params, string $img): string {
    // Figure
    $figureClasses = ['size-' . $params['size_slug']];
    if ($params['align']) {
      $figureClasses[] = 'align' . $params['align'];
    }
    // Link
    if ($params['href']) {
      $img = $this->wrapToLink($params, $img);
    }
    $caption = isset($params['caption']) && $params['caption'] ? "<figcaption>{$this->htmlSanitizer->sanitize($params['caption'])}</figcaption>" : '';
    $figure = '<figure class="' . $this->wp->escAttr(implode(' ', $figureClasses)) . '">' . $img . $caption . '</figure>';
    // Main wrapper
    $divClasses = ['mailpoet_form_image'];
    if (trim($params['class_name'])) {
      $divClasses[] = trim($params['class_name']);
    }
    return '<div class="' . $this->wp->escAttr(implode(' ', $divClasses)) . '">' . $figure . '</div>';
  }

  private function wrapToLink(array $params, string $img): string {
    $attributes = ['href="' . $this->wp->escUrl($params['href']) . '"'];
    if ($params['link_class']) {
      $attributes[] = 'class="' . $this->wp->escAttr($params['link_class']) . '"';
    }
    if ($params['link_target']) {
      $attributes[] = 'target="' . $this->wp->escAttr($params['link_target']) . '"';
    }
    if ($params['rel']) {
      $attributes[] = 'rel="' . $this->wp->escAttr($params['rel']) . '"';
    }
    return '<a ' . implode(' ', $attributes) . ' >' . $img . '</a>';
  }
}
