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
  private $page_renderer;

  /** @var State */
  private $tasks_state;

  /** @var CronHelper */
  private $cron_helper;

  function __construct(PageRenderer $page_renderer, State $tasks_state, CronHelper $cron_helper) {
    $this->page_renderer = $page_renderer;
    $this->tasks_state = $tasks_state;
    $this->cron_helper = $cron_helper;
  }

  function render() {
    $system_info_data = Beacon::getData();
    $cron_ping_response = $this->cron_helper->pingDaemon();
    $system_status_data = [
      'cron' => [
        'url' => $this->cron_helper->getCronUrl(CronDaemon::ACTION_PING),
        'isReachable' => $this->cron_helper->validatePingResponse($cron_ping_response),
        'pingResponse' => $cron_ping_response,
      ],
      'mss' => [
        'enabled' => (Bridge::isMPSendingServiceEnabled()) ?
          ['isReachable' => Bridge::pingBridge()] :
          false,
      ],
      'cronStatus' => $this->cron_helper->getDaemon(),
      'queueStatus' => MailerLog::getMailerLog(),
    ];
    $system_status_data['cronStatus']['accessible'] = $this->cron_helper->isDaemonAccessible();
    $system_status_data['queueStatus']['tasksStatusCounts'] = $this->tasks_state->getCountsPerStatus();
    $system_status_data['queueStatus']['latestTasks'] = $this->tasks_state->getLatestTasks(Sending::TASK_TYPE);
    $this->page_renderer->displayPage(
      'help.html',
      [
        'systemInfoData' => $system_info_data,
        'systemStatusData' => $system_status_data,
      ]
    );
  }
}
