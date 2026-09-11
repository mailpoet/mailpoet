<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter;

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
    if (isset($body['content']['blocks']) && is_array($body['content']['blocks'])) {
      $body['content']['blocks'] = $this->sanitizeBlocks($body['content']['blocks']);
    }
    if (isset($body['blockDefaults']) && is_array($body['blockDefaults'])) {
      $body['blockDefaults'] = $this->sanitizeBlockDefaults($body['blockDefaults']);
    }
    return $body;
  }

  private function sanitizeBlocks(array $blocks): array {
    foreach ($blocks as $key => $block) {
      if (!is_array($block)) {
        continue;
      }
      $blocks[$key] = $this->sanitizeProperties($block, $block['type'] ?? null);
      if (isset($block['blocks']) && is_array($block['blocks'])) {
        $blocks[$key]['blocks'] = $this->sanitizeBlocks($block['blocks']);
      }
    }
    return $blocks;
  }

  private function sanitizeBlockDefaults(array $blockDefaults): array {
    foreach ($blockDefaults as $type => $defaults) {
      if (!is_array($defaults)) {
        continue;
      }
      $blockDefaults[$type] = $this->sanitizeProperties($defaults, $type);
    }
    return $blockDefaults;
  }

  /**
   * @param mixed $type
   */
  private function sanitizeProperties(array $block, $type): array {
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
