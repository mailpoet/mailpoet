<?php

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;

class AssetsLoader {

  /** @var Renderer */
  private $renderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(RendererFactory $rendererFactory, WPFunctions $wp) {
    $this->renderer = $rendererFactory->getRenderer();
    $this->wp = $wp;
  }

  public function loadStyles(): void {
    if (isset($_GET['page']) && $_GET['page'] === 'mailpoet-form-editor') {
      // Because 3rd styles were moved into admin styles, the dependency guarantees correct overriding
      $this->enqueueStyle('mailpoet-form-editor', ['mailpoet-admin-global']);
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
