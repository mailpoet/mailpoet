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
    if (isset($body['content']) && isset($body['content']['blocks']) && is_array($body['content']['blocks'])) {
      $body['content']['blocks'] = $this->sanitizeBlocks($body['content']['blocks']);
    }
    return $body;
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
    $type = $block['type'] ?? null;
    if (!is_string($type) || !isset(self::SANITIZATION_CONFIG[$type])) {
      return $block;
    }
    foreach (self::SANITIZATION_CONFIG[$type] as $property) {
      if (!isset($block[$property])) {
        continue;
      }
      $block[$property] = $this->htmlSanitizer->sanitize($block[$property]);
    }
    return $block;
  }
}
