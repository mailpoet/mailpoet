<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Models\ScheduledTask;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;

class AuthorizedSendingEmailsCheck extends SimpleWorker {
  const TASK_TYPE = 'authorized_email_addresses_check';
  const AUTOMATIC_SCHEDULING = false;

  /** @var AuthorizedEmailsController */
  private $authorized_emails_controller;

  public function __construct(AuthorizedEmailsController $authorized_emails_controller) {
    $this->authorized_emails_controller = $authorized_emails_controller;
    parent::__construct();
  }

  public function checkProcessingRequirements() {
    return Bridge::isMPSendingServiceEnabled();
  }

  public function processTaskStrategy(ScheduledTask $task, $timer) {
    $this->authorized_emails_controller->checkAuthorizedEmailAddresses();
    return true;
  }
}
