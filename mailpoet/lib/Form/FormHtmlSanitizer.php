<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form;

use MailPoet\WP\Functions as WPFunctions;

class FormHtmlSanitizer {
  /** @var WPFunctions */
  private $wp;

  /**
   * @var array
   * Configuration of allowed tags for form blocks that may contain some html.
   * Covers all tags available in the form editor's Rich Text component
   * This doesn't cover CustomHTML block.
   */
  private $allowedHtml = [
    'a' => [
      'href' => true,
      'title' => true,
      'data-id' => true,
      'data-type' => true,
      'target' => true,
      'rel' => true,
    ],
    'br' => [],
    'code' => [],
    'em' => [],
    'img' => [
      'class' => true,
      'style' => true,
      'src' => true,
      'alt' => true,
    ],
    'kbd' => [],
    'span' => [
      'style' => true,
      'data-font' => true,
      'class' => true,
    ],
    'mark' => [
      'style' => true,
      'class' => true,
    ],
    'strong' => [],
    'sub' => [],
    'sup' => [],
    's' => [],
  ];

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function sanitize(string $html): string {
    return $this->wp->wpKses($html, $this->allowedHtml);
  }
}
