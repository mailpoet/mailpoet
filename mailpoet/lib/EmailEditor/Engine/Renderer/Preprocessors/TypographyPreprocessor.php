<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Preprocessors;

class TypographyPreprocessor implements Preprocessor {
  /**
   * List of styles that should be copied from parent to children.
   * @var string[]
   */
  private const TYPOGRAPHY_STYLES = [
    'color',
    'font-size',
    'font-family',
  ];

  public function preprocess(array $parsedBlocks, array $layoutStyles): array {
    foreach ($parsedBlocks as $key => $block) {
      $block = $this->preprocessParent($block);
      $block['innerBlocks'] = $this->copyTypographyFromParent($block['innerBlocks'], $block);
      $parsedBlocks[$key] = $block;
    }
    return $parsedBlocks;
  }

  private function copyTypographyFromParent(array $children, array $parent): array {
    foreach ($children as $key => $child) {
      $child = $this->preprocessParent($child);
      $child['email_attrs'] = array_merge($this->filterStyles($parent['email_attrs']), $child['email_attrs']);
      $child['innerBlocks'] = $this->copyTypographyFromParent($child['innerBlocks'] ?? [], $child);
      $children[$key] = $child;
    }

    return $children;
  }

  private function preprocessParent(array $block): array {
    // Build styles that should be copied to children
    $emailAttrs = [];
    if (isset($block['attrs']['style']['color']['text'])) {
      $emailAttrs['color'] = $block['attrs']['style']['color']['text'];
    }
    if (isset($block['attrs']['style']['typography']['fontFamily'])) {
      $emailAttrs['font-family'] = $block['attrs']['style']['typography']['fontFamily'];
    }
    if (isset($block['attrs']['style']['typography']['fontSize'])) {
      $emailAttrs['font-size'] = $block['attrs']['style']['typography']['fontSize'];
    }
    $block['email_attrs'] = array_merge($emailAttrs, $block['email_attrs'] ?? []);
    return $block;
  }

  private function filterStyles(array $styles): array {
    return array_intersect_key($styles, array_flip(self::TYPOGRAPHY_STYLES));
  }
}
