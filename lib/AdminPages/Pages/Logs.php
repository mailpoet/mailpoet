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
    $data = ['logs' => []];
    foreach($logs as $log) {
      $data['logs'][] = [
        'name' => $log->getName(),
        'message' => $log->getMessage(),
        'short_message' => substr($log->getMessage(), 0, 150),
        'created_at' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
      ];
    }
    $this->pageRenderer->displayPage('logs.html', $data);
  }
}
