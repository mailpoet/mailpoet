<?php
namespace MailPoet\Cron\Workers;

use MailPoet\Models\ScheduledTask;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;

if (!defined('ABSPATH')) exit;

class AuthorizedSendingEmailsCheck extends SimpleWorker {
  const TASK_TYPE = 'authorized_email_addresses_check';
  const AUTOMATIC_SCHEDULING = false;

  /** @var AuthorizedEmailsController */
  private $authorized_emails_controller;

  function __construct(AuthorizedEmailsController $authorized_emails_controller, $timer = false) {
    $this->authorized_emails_controller = $authorized_emails_controller;
    parent::__construct($timer);
  }

  function checkProcessingRequirements() {
    return Bridge::isMPSendingServiceEnabled();
  }

  function processTaskStrategy(ScheduledTask $task) {
    $this->authorized_emails_controller->checkAuthorizedEmailAddresses();
    return true;
  }
}
