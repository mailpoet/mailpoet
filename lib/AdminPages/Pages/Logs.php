<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Logging\LogRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Logs {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var LogRepository */
  private $logRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    LogRepository $logRepository,
    WPFunctions $wp,
    PageRenderer $pageRenderer
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
    $this->logRepository = $logRepository;
  }

  public function render() {
    $dateFrom = (new Carbon())->subDays(7);
    $dateTo = new Carbon();
    $logs = $this->logRepository->getLogs($dateFrom, $dateTo);
    $data = ['logs' => []];
    foreach($logs as $log) {
      $created = wp_date(
        (get_option('date_format') ?: 'F j, Y')
        . ' '
        . (get_option('time_format') ?: 'g:i a'),
        (new Carbon($log->getCreatedAt()))->getTimestamp()
      );
      $data['logs'][] = [
        'name' => $log->getName(),
        'message' => $log->getMessage(),
        'short_message' => substr($log->getMessage(), 0, 150),
        'created_at' => $created,
      ];
    }
    $this->pageRenderer->displayPage('logs.html', $data);
  }
}
