<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\Logging\LogsPageData;

class Logs {
  /** @var AssetsController */
  private $assetsController;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var LogsPageData */
  private $logsPageData;

  public function __construct(
    AssetsController $assetsController,
    PageRenderer $pageRenderer,
    LogsPageData $logsPageData
  ) {
    $this->assetsController = $assetsController;
    $this->pageRenderer = $pageRenderer;
    $this->logsPageData = $logsPageData;
  }

  public function render() {
    $this->assetsController->setupDataViewsDependencies();
    $this->pageRenderer->displayPage('logs.html', $this->logsPageData->get());
  }
}
