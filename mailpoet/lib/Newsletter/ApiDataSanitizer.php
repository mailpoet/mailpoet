<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter;

use MailPoet\Newsletter\Renderer\StylesHelper;

class ApiDataSanitizer {
  /** @var NewsletterHtmlSanitizer */
  private $htmlSanitizer;

  /**
   * Configuration specifies which block types and properties within newsletters content blocks are sanitized
   */
  private const SANITIZATION_CONFIG = [
    'header' => ['text'],
    'footer' => ['text'],
    'text' => ['text'],
  ];

  public function __construct(
    NewsletterHtmlSanitizer $htmlSanitizer
  ) {
    $this->htmlSanitizer = $htmlSanitizer;
  }

  public function sanitizeBody(array $body): array {
    if (isset($body['content']) && isset($body['content']['blocks']) && is_array($body['content']['blocks'])) {
      $body['content']['blocks'] = $this->sanitizeBlocks($body['content']['blocks']);
    }
    if (isset($body['blockDefaults']) && is_array($body['blockDefaults'])) {
      $body['blockDefaults'] = $this->sanitizeBlockDefaults($body['blockDefaults']);
    }
    foreach (['content', 'blockDefaults'] as $key) {
      if (isset($body[$key]) && is_array($body[$key])) {
        $body[$key] = $this->sanitizeBlockStyles($body[$key]);
      }
    }
    if (isset($body['globalStyles']) && is_array($body['globalStyles'])) {
      $body['globalStyles'] = $this->sanitizeGlobalStyles($body['globalStyles']);
    }
    return $body;
  }

  /**
   * Global styles end up in a <style> element in the editor and the email. Invalid values are removed,
   * so the editor falls back to its defaults; the email renderer skips them too.
   */
  private function sanitizeGlobalStyles(array $globalStyles): array {
    foreach ($globalStyles as $group => $styles) {
      if (!is_array($styles)) {
        continue;
      }
      foreach ($styles as $property => $value) {
        if (!$this->isValidStyleProperty($property) || !StylesHelper::isValidCssValue($value)) {
          unset($globalStyles[$group][$property]);
        }
      }
    }
    return $globalStyles;
  }

  /**
   * Walks blocks and their nested parts (e.g. a posts block's read more button) and empties
   * style values that could end the CSS declaration. Renderers read the keys directly, so they stay.
   */
  private function sanitizeBlockStyles(array $data): array {
    foreach ($data as $key => $value) {
      if (!is_array($value)) {
        continue;
      }
      $data[$key] = $key === 'styles' ? $this->sanitizeBlockStyleGroups($value) : $this->sanitizeBlockStyles($value);
    }
    return $data;
  }

  private function sanitizeBlockStyleGroups(array $groups): array {
    foreach ($groups as $group => $styles) {
      if (!is_array($styles)) {
        continue;
      }
      foreach ($styles as $property => $value) {
        if (!$this->isValidStyleProperty($property)) {
          unset($groups[$group][$property]);
        } elseif (!StylesHelper::isValidCssValue($value)) {
          $groups[$group][$property] = '';
        }
      }
    }
    return $groups;
  }

  /**
   * Style keys are camelCase or hyphenated CSS property names, e.g. backgroundColor or border-radius.
   * @param int|string $property
   */
  private function isValidStyleProperty($property): bool {
    return is_string($property)
      && $property !== ''
      && strspn($property, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-') === strlen($property);
  }

  private function sanitizeBlockDefaults(array $blockDefaults): array {
    foreach ($blockDefaults as $type => $defaults) {
      if (!is_array($defaults)) {
        continue;
      }
      $blockDefaults[$type] = $this->sanitizeBlockProperties($defaults, $type);
    }
    return $blockDefaults;
  }

  private function sanitizeBlocks(array $blocks): array {
    foreach ($blocks as $key => $block) {
      if (!is_array($block)) {
        continue;
      }
      $block = $this->sanitizeBlock($block);
      if (isset($block['blocks']) && is_array($block['blocks'])) {
        $block['blocks'] = $this->sanitizeBlocks($block['blocks']);
      }
      $blocks[$key] = $block;
    }
    return $blocks;
  }

  private function sanitizeBlock(array $block): array {
    return $this->sanitizeBlockProperties($block, $block['type'] ?? null);
  }

  /**
   * @param mixed $type
   */
  private function sanitizeBlockProperties(array $block, $type): array {
    if (!is_string($type) || !isset(self::SANITIZATION_CONFIG[$type])) {
      return $block;
    }
    foreach (self::SANITIZATION_CONFIG[$type] as $property) {
      if (!isset($block[$property])) {
        continue;
      }
      $block[$property] = $this->sanitizeProperty($block[$property]);
    }
    return $block;
  }

  /**
   * @param mixed $value
   */
  private function sanitizeProperty($value): string {
    if (!is_scalar($value)) {
      return '';
    }
    return $this->htmlSanitizer->sanitize((string)$value);
  }
}
