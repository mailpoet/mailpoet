<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\WP\Functions as WPFunctions;

class Tags {
  /** @var AssetsController */
  private $assetsController;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    AssetsController $assetsController,
    PageRenderer $pageRenderer,
    WPFunctions $wp
  ) {
    $this->assetsController = $assetsController;
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
  }

  public function render(): void {
    $this->assetsController->setupTagsDependencies();
    $this->pageRenderer->displayPage('subscribers/tags.html', [
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
      'subscribers_listing_url' => $this->wp->escUrlRaw($this->wp->adminUrl('admin.php?page=mailpoet-subscribers')),
    ]);
  }
}
