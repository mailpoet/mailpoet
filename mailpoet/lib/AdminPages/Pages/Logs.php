<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\Logging\LogRepository;
use MailPoet\Logging\LogsDownload;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Logs {
  /** @var AssetsController */
  private $assetsController;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  /** @var LogRepository */
  private $logRepository;

  public function __construct(
    AssetsController $assetsController,
    PageRenderer $pageRenderer,
    WPFunctions $wp,
    LogRepository $logRepository
  ) {
    $this->assetsController = $assetsController;
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
    $this->logRepository = $logRepository;
  }

  public function render() {
    $this->assetsController->setupDataViewsDependencies();

    $dateFrom = (new Carbon())->subDays(7);
    $data = [
      'logs_default_from' => $dateFrom->format('Y-m-d'),
      'logs_filter_options' => [
        'names' => $this->logRepository->getDistinctNames(),
      ],
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
      'download' => [
        'action_url' => admin_url('admin-post.php'),
        'nonce' => LogsDownload::createNonce($this->wp),
      ],
    ];
    $this->pageRenderer->displayPage('logs.html', $data);
  }
}
