<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form;

use MailPoet\WP\Functions as WPFunctions;

class ApiDataSanitizer {
  /** @var FormHtmlSanitizer */
  private $htmlSanitizer;

  /** @var WPFunctions */
  private $wp;

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
    'checkbox' => [
      'values',
    ],
  ];

  public function __construct(
    FormHtmlSanitizer $htmlSanitizer,
    WPFunctions $wp
  ) {
    $this->htmlSanitizer = $htmlSanitizer;
    $this->wp = $wp;
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

  public function sanitizeBlock(array $block): array {
    if (isset($block['id'])) {
      $block['id'] = is_scalar($block['id']) ? $this->wp->sanitizeKey((string)$block['id']) : '';
    }
    if (!isset($this->htmlSanitizeConfig[$block['type']])) {
      return $block;
    }
    $params = $block['params'] ?? [];
    foreach ($this->htmlSanitizeConfig[$block['type']] as $parameter) {
      if (!isset($params[$parameter])) continue;

      if ($parameter === 'values' && is_array($params[$parameter])) {
        $params[$parameter] = $this->sanitizeValues($params[$parameter]);
      } else {
        $params[$parameter] = $this->htmlSanitizer->sanitize($params[$parameter]);
      }

    }
    $block['params'] = $params;
    return $block;
  }

  private function sanitizeValues(array $values) {
    foreach ($values as $key => $value) {
      if (!isset($value['value'])) continue;
      $values[$key]['value'] = $this->htmlSanitizer->sanitize($value['value']);
    }
    return $values;
  }
}
