<?php

namespace MailPoet\Form;

class ApiDataSanitiser {
  /** @var FormHtmlSanitiser */
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

  public function __construct(FormHtmlSanitiser $htmlSanitiser) {
    $this->htmlSanitizer = $htmlSanitiser;
  }

  public function sanitiseBody(array $body): array {
    foreach ($body as $key => $block) {
      $sanitizedBlock = $this->sanitiseBlock($block);
      if (isset($sanitizedBlock['body']) && is_array($sanitizedBlock['body']) && !empty($sanitizedBlock['body'])) {
        $sanitizedBlock['body'] = $this->sanitiseBody($sanitizedBlock['body']);
      }
      $body[$key] = $sanitizedBlock;
    }
    return $body;
  }

  private function sanitiseBlock(array $block): array {
    if (!isset($this->htmlSanitizeConfig[$block['type']])) {
      return $block;
    }
    $params = $block['params'] ?? [];
    foreach ($this->htmlSanitizeConfig[$block['type']] as $parameter) {
      if (!isset($params[$parameter])) continue;
      $params[$parameter] = $this->htmlSanitizer->sanitise($params[$parameter]);
    }
    $block['params'] = $params;
    return $block;
  }
}
