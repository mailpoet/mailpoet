<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Models\ScheduledTask;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;

class AuthorizedSendingEmailsCheck extends SimpleWorker {
  const TASK_TYPE = 'authorized_email_addresses_check';
  const AUTOMATIC_SCHEDULING = false;

  /** @var AuthorizedEmailsController */
  private $authorizedEmailsController;

  public function __construct(
    AuthorizedEmailsController $authorizedEmailsController
  ) {
    $this->authorizedEmailsController = $authorizedEmailsController;
    parent::__construct();
  }

  public function checkProcessingRequirements() {
    return Bridge::isMPSendingServiceEnabled();
  }

  public function processTaskStrategy(ScheduledTask $task, $timer) {
    $this->authorizedEmailsController->checkAuthorizedEmailAddresses();
    return true;
  }
}
