<?php
namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Models\ScheduledTask;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\InactiveSubscribersController;

if (!defined('ABSPATH')) exit;

class AuthorizedSendingEmailsCheck extends SimpleWorker {
  const TASK_TYPE = 'authorized_email_addresses_check';
  const AUTOMATIC_SCHEDULING = false;

  /** @var Bridge */
  private $bridge;

  function __construct(Bridge $bridge, $timer = false) {
    $this->bridge = $bridge;
    parent::__construct($timer);
  }

  function checkProcessingRequirements() {
    return Bridge::isMPSendingServiceEnabled();
  }

  function processTaskStrategy(ScheduledTask $task) {
    $this->bridge->checkAuthorizedEmailAddresses();
    return true;
  }
}
