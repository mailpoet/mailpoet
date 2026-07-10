<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form\Block;

use MailPoet\Form\BlockStylesRenderer;
use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\WP\Functions as WPFunctions;

class Close {

  /** @var BlockRendererHelper */
  private $rendererHelper;

  /** @var BlockWrapperRenderer */
  private $wrapper;

  /** @var BlockStylesRenderer */
  private $stylesRenderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    BlockRendererHelper $rendererHelper,
    BlockWrapperRenderer $wrapper,
    BlockStylesRenderer $stylesRenderer,
    WPFunctions $wp
  ) {
    $this->rendererHelper = $rendererHelper;
    $this->wrapper = $wrapper;
    $this->stylesRenderer = $stylesRenderer;
    $this->wp = $wp;
  }

  public function render(array $block, array $formSettings): string {
    $html = '<button type="button" class="mailpoet_form_close" ';

    $html .= 'data-automation-id="form_close_button" ';

    if (isset($block['styles']['font_family'])) {
      $html .= "data-font-family='{$this->wp->escAttr($block['styles']['font_family'])}' ";
    }

    $styles = $this->stylesRenderer->renderForButton($block['styles'] ?? [], $formSettings);

    if ($styles) {
      $html .= 'style="' . $this->wp->escAttr($styles) . '" ';
    }

    $html .= '>';

    $html .= $this->rendererHelper->getFieldLabel($block);

    $html .= '</button>';

    return $this->wrapper->render($block, $html);
  }
}
