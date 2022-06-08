<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Cron\CronHelper;
use MailPoet\Helpscout\Beacon;
use MailPoet\Mailer\MailerLog;
use MailPoet\Router\Endpoints\CronDaemon;
use MailPoet\Services\Bridge;
use MailPoet\Tasks\Sending;
use MailPoet\Tasks\State;

class Help {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var State */
  private $tasksState;

  /** @var CronHelper */
  private $cronHelper;

  /** @var Beacon */
  private $helpscoutBeacon;

  /** @var Bridge $bridge */
  private $bridge;

  public function __construct(
    PageRenderer $pageRenderer,
    State $tasksState,
    CronHelper $cronHelper,
    Beacon $helpscoutBeacon,
    Bridge $bridge
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->tasksState = $tasksState;
    $this->cronHelper = $cronHelper;
    $this->helpscoutBeacon = $helpscoutBeacon;
    $this->bridge = $bridge;
  }

  public function render() {
    $systemInfoData = $this->helpscoutBeacon->getData(true);
    try {
      $cronPingUrl = $this->cronHelper->getCronUrl(CronDaemon::ACTION_PING);
      $cronPingResponse = $this->cronHelper->pingDaemon();
    } catch (\Exception $e) {
      $cronPingResponse = __('Can‘t generate cron URL.', 'mailpoet') . ' (' . $e->getMessage() . ')';
      $cronPingUrl = $cronPingResponse;
    }

    $mailerLog = MailerLog::getMailerLog();
    $mailerLog['sent'] = MailerLog::sentSince();
    $systemStatusData = [
      'cron' => [
        'url' => $cronPingUrl,
        'isReachable' => $this->cronHelper->validatePingResponse($cronPingResponse),
        'pingResponse' => $cronPingResponse,
      ],
      'mss' => [
        'enabled' => $this->bridge->isMailpoetSendingServiceEnabled(),
        'isReachable' => $this->bridge->pingBridge(),
      ],
      'cronStatus' => $this->cronHelper->getDaemon(),
      'queueStatus' => $mailerLog,
    ];
    $systemStatusData['cronStatus']['accessible'] = $this->cronHelper->isDaemonAccessible();
    $systemStatusData['queueStatus']['tasksStatusCounts'] = $this->tasksState->getCountsPerStatus();
    $systemStatusData['queueStatus']['latestTasks'] = $this->tasksState->getLatestTasks(Sending::TASK_TYPE);
    $this->pageRenderer->displayPage(
      'help.html',
      [
        'systemInfoData' => $systemInfoData,
        'systemStatusData' => $systemStatusData,
      ]
    );
  }
}
