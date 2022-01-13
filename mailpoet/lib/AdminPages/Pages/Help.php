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

  public function __construct(
    PageRenderer $pageRenderer,
    State $tasksState,
    CronHelper $cronHelper,
    Beacon $helpscoutBeacon
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->tasksState = $tasksState;
    $this->cronHelper = $cronHelper;
    $this->helpscoutBeacon = $helpscoutBeacon;
  }

  public function render() {
    $systemInfoData = $this->helpscoutBeacon->getData(true);
    $cronPingResponse = $this->cronHelper->pingDaemon();
    $systemStatusData = [
      'cron' => [
        'url' => $this->cronHelper->getCronUrl(CronDaemon::ACTION_PING),
        'isReachable' => $this->cronHelper->validatePingResponse($cronPingResponse),
        'pingResponse' => $cronPingResponse,
      ],
      'mss' => [
        'enabled' => (Bridge::isMPSendingServiceEnabled()) ?
          ['isReachable' => Bridge::pingBridge()] :
          false,
      ],
      'cronStatus' => $this->cronHelper->getDaemon(),
      'queueStatus' => MailerLog::getMailerLog(),
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
