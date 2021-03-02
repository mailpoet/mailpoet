<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Logging\LogRepository;
use MailPoetVendor\Carbon\Carbon;

class Logs {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var LogRepository */
  private $logRepository;

  public function __construct(
    LogRepository $logRepository,
    PageRenderer $pageRenderer
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->logRepository = $logRepository;
  }

  public function render() {
    $dateFrom = (new Carbon())->subDays(7);
    $dateTo = new Carbon();
    $logs = $this->logRepository->getLogs($dateFrom, $dateTo);
    
    $this->pageRenderer->displayPage('logs.html', []);
  }
}
