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

if (!defined('ABSPATH')) exit;

class Help {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var State */
  private $tasks_state;

  function __construct(PageRenderer $page_renderer, State $tasks_state) {
    $this->page_renderer = $page_renderer;
    $this->tasks_state = $tasks_state;
  }

  function render() {
    $system_info_data = Beacon::getData();
    $system_status_data = [
      'cron' => [
        'url' => CronHelper::getCronUrl(CronDaemon::ACTION_PING),
        'isReachable' => CronHelper::pingDaemon(true),
      ],
      'mss' => [
        'enabled' => (Bridge::isMPSendingServiceEnabled()) ?
          ['isReachable' => Bridge::pingBridge()] :
          false,
      ],
      'cronStatus' => CronHelper::getDaemon(),
      'queueStatus' => MailerLog::getMailerLog(),
    ];
    $system_status_data['cronStatus']['accessible'] = CronHelper::isDaemonAccessible();
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
