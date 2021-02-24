<?php

namespace MailPoet\Newsletter;

class ApiDataSanitizer {
  /** @var NewsletterHtmlSanitizer */
  private $htmlSanitizer;

  private const SANITIZE_KEY_WHITELIST = [
    'text',
  ];

  public function __construct(NewsletterHtmlSanitizer $htmlSanitizer) {
    $this->htmlSanitizer = $htmlSanitizer;
  }

  public function sanitizeBody(array $body): array {
    foreach ($body as $blockName => $block) {
      if (is_array($block)) {
        $sanitizedBlock = $this->sanitizeBody($block);
      } else {
        $sanitizedBlock = $block && in_array($blockName, self::SANITIZE_KEY_WHITELIST, true) ? $this->htmlSanitizer->sanitize($block) : $block;
      }
      $body[$blockName] = $sanitizedBlock;
    }

    return $body;
  }
}
