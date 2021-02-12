<?php

namespace MailPoet\Form;

class ApiDataSanitizer {
  /** @var FormHtmlSanitizer */
  private $htmlSanitizer;

  /**
   * List of blocks and their parameters that will be sanitized
   * @var string[][]
   */
  private $htmlSanitizeConfig = [
    'paragraph' => [
      'content',
    ],
    'heading' => [
      'content',
    ],
    'image' => [
      'caption',
    ],
  ];

  public function __construct(FormHtmlSanitizer $htmlSanitizer) {
    $this->htmlSanitizer = $htmlSanitizer;
  }

  public function sanitizeBody(array $body): array {
    foreach ($body as $key => $block) {
      $sanitizedBlock = $this->sanitizeBlock($block);
      if (isset($sanitizedBlock['body']) && is_array($sanitizedBlock['body']) && !empty($sanitizedBlock['body'])) {
        $sanitizedBlock['body'] = $this->sanitizeBody($sanitizedBlock['body']);
      }
      $body[$key] = $sanitizedBlock;
    }
    return $body;
  }

  private function sanitizeBlock(array $block): array {
    if (!isset($this->htmlSanitizeConfig[$block['type']])) {
      return $block;
    }
    $params = $block['params'] ?? [];
    foreach ($this->htmlSanitizeConfig[$block['type']] as $parameter) {
      if (!isset($params[$parameter])) continue;
      $params[$parameter] = $this->htmlSanitizer->sanitize($params[$parameter]);
    }
    $block['params'] = $params;
    return $block;
  }
}
