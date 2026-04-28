<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\Listing\PageLimit;
use MailPoet\WP\Functions as WPFunctions;

class StaticSegments {
  /** @var AssetsController */
  private $assetsController;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var PageLimit */
  private $listingPageLimit;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    AssetsController $assetsController,
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    WPFunctions $wp
  ) {
    $this->assetsController = $assetsController;
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->wp = $wp;
  }

  /**
   * @return void
   */
  public function render() {
    $this->assetsController->setupDataViewsDependencies();

    $data = [];
    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('segments');
    $data['api'] = [
      'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
      'nonce' => $this->wp->wpCreateNonce('wp_rest'),
    ];

    $this->pageRenderer->displayPage('segments/static.html', $data);
  }
}
