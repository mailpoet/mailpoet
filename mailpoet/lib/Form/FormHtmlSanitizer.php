<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form;

use MailPoet\WP\Functions as WPFunctions;

class FormHtmlSanitizer {

  /**
   * @var array
   * Configuration of allowed tags for form blocks that may contain some html.
   * Covers all tags available in the form editor's Rich Text component and which we allow in checkbox label.
   * This doesn't cover CustomHTML block.
   */
  const ALLOWED_HTML = [
    'a' => [
      'class' => true,
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
    'math' => [
      'data-latex' => true,
      'display' => true,
    ],
    'semantics' => [],
    'annotation' => [
      'encoding' => true,
    ],
    'mrow' => [],
    'mi' => [],
    'mn' => [],
    'mo' => [
      'movablelimits' => true,
    ],
    'mtext' => [],
    'mspace' => [
      'height' => true,
      'width' => true,
    ],
    'mfrac' => [
      'linethickness' => true,
    ],
    'msqrt' => [],
    'mroot' => [],
    'msub' => [],
    'msup' => [],
    'msubsup' => [],
    'munder' => [],
    'mover' => [],
    'munderover' => [],
    'mtable' => [
      'columnalign' => true,
      'rowspacing' => true,
      'columnspacing' => true,
    ],
    'mtr' => [],
    'mtd' => [
      'columnalign' => true,
      'rowalign' => true,
      'style' => true,
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
  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function sanitize(string $html): string {
    return $this->wp->wpKses($html, self::ALLOWED_HTML);
  }
}
