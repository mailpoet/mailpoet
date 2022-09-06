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
    if (isset($_GET['page']) && strpos($_GET['page'], 'mailpoet-') === 0) {
      $this->enqueueStyle('mailpoet-plugin', [
        'forms', // To prevent conflict in CSS with WP forms we need to add dependency
        'buttons',
      ]);
    }
    if (isset($_GET['page']) && $_GET['page'] === 'mailpoet-form-editor') {
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
