<?php

namespace MailPoet\Newsletter;

class ApiDataSanitizer {
  /** @var NewsletterHtmlSanitizer */
  private $htmlSanitizer;

  public function __construct(NewsletterHtmlSanitizer $htmlSanitizer) {
    $this->htmlSanitizer = $htmlSanitizer;
  }

  public function sanitizeBody(array $body): array {
    foreach ($body as $blockName => $block) {
      $sanitizedBlock = is_array($block) ? $this->sanitizeBlock($block) : $this->htmlSanitizer->sanitize($block);
      $body[$blockName] = $sanitizedBlock;
    }

    return $body;
  }

  private function sanitizeBlock(array $block): array {
    foreach ($block as $name => $value) {
      if (is_array($value)) {
        $block[$name] = $this->sanitizeBlock($value);
      } else {
        $block[$name] = $value ? $this->htmlSanitizer->sanitize($value) : $value;
      }
    }
    return $block;
  }
}
