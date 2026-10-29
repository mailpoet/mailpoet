<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;

class AssetsLoader {
  private const BUNDLED_DESIGN_TOKENS_STYLE = 'mailpoet-wpds-tokens';

  private const FORM_EDITOR_WORDPRESS_STYLE_DEPENDENCIES = [
    'wp-components',
    'wp-block-library',
    'wp-block-library-theme',
    'wp-block-editor',
    'wp-edit-blocks',
    'wp-editor',
    'wp-edit-post',
    'wp-format-library',
  ];

  /** @var Renderer */
  private $renderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    RendererFactory $rendererFactory,
    WPFunctions $wp
  ) {
    $this->renderer = $rendererFactory->getRenderer();
    $this->wp = $wp;
  }

  public function loadStyles(): void {
    // MailPoet plugin style should be loaded on all mailpoet sites
    $page = isset($_GET['page']) && is_string($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : null;
    if ($page && strpos($page, 'mailpoet-') === 0) {
      $designTokensHandle = $this->getDesignTokensStyleHandle();
      if ($designTokensHandle === self::BUNDLED_DESIGN_TOKENS_STYLE) {
        $this->enqueueStyle(self::BUNDLED_DESIGN_TOKENS_STYLE);
      }
      $this->enqueueStyle('mailpoet-plugin', [
        'forms', // To prevent conflict in CSS with WP forms we need to add dependency
        'buttons',
        // @wordpress/components styles come from the core `wp-components` handle and must
        // load before MailPoet overrides. WordPress 7.1+ prints them on every admin screen
        // (command palette), so a bundled copy would apply the same styles twice.
        'wp-components',
        $designTokensHandle,
      ]);
    }
    if ($page === 'mailpoet-form-editor') {
      $this->enqueueStyle(
        'mailpoet-form-editor',
        array_merge(['mailpoet-plugin'], self::FORM_EDITOR_WORDPRESS_STYLE_DEPENDENCIES)
      );
      $this->enqueueStyle('mailpoet-public');
    }
    if ($page === 'mailpoet-form-editor-template-selection') {
      $this->enqueueStyle('mailpoet-form-editor', ['mailpoet-plugin', 'wp-components']);
    }
    // We reuse a part of CSS in the newsletter editor
    if ($page === 'mailpoet-newsletter-editor') {
      $this->enqueueStyle('mailpoet-form-editor', ['mailpoet-plugin']);
    }
  }

  /**
   * Get the handle of the WordPress Design System tokens stylesheet.
   *
   * Uses the `wp-theme` style registered by WordPress 7.1+ or the Gutenberg plugin when available,
   * and falls back to the copy of the tokens bundled with MailPoet otherwise. The caller lists
   * the handle as a style dependency, so WordPress enqueues it.
   *
   * The tokens are used by our styles, @wordpress/ui, @wordpress/dataviews and @wordpress/admin-ui.
   * The bundled fallback can be removed once WP 7.1 is the minimum supported version.
   */
  private function getDesignTokensStyleHandle(): string {
    return $this->wp->wpStyleIs('wp-theme', 'registered') ? 'wp-theme' : self::BUNDLED_DESIGN_TOKENS_STYLE;
  }

  private function enqueueStyle(string $name, array $deps = []): void {
    $this->wp->wpEnqueueStyle(
      $name,
      Env::$assetsUrl . '/dist/css/' . $this->renderer->getCssAsset("{$name}.css"),
      $deps
    );
  }
}
