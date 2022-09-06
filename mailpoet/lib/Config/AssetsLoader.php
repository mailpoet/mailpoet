<?php

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;

class AssetsLoader {

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
    $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : null;
    if ($page && strpos($page, 'mailpoet-') === 0) {
      $this->enqueueStyle('mailpoet-plugin', [
        'forms', // To prevent conflict in CSS with WP forms we need to add dependency
        'buttons',
      ]);
    }
    if ($page === 'mailpoet-form-editor') {
      // Form-editor CSS has to be loaded after plugin style because it contains @wordpress/components dependency
      $this->enqueueStyle('mailpoet-form-editor', ['mailpoet-plugin']);
      $this->enqueueStyle('mailpoet-public');
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
