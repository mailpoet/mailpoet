<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;

class AssetsLoader {
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
      $this->enqueueStyle('mailpoet-plugin', [
        'forms', // To prevent conflict in CSS with WP forms we need to add dependency
        'buttons',
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

  private function enqueueStyle(string $name, array $deps = []): void {
    $this->wp->wpEnqueueStyle(
      $name,
      Env::$assetsUrl . '/dist/css/' . $this->renderer->getCssAsset("{$name}.css"),
      $deps
    );
  }
}
