<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Logs {
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

  public function render() {
    $this->assetsController->setupDataViewsDependencies();

    $dateFrom = (new Carbon())->subDays(7);
    $data = [
      'logs_default_from' => $dateFrom->format('Y-m-d'),
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
    ];
    $this->pageRenderer->displayPage('logs.html', $data);
  }
}
