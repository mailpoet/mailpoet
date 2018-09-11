<?php
namespace MailPoet\Cron\Workers\SendingQueue;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;

class SendingErrorHandler {
  function processError(MailerError $error) {
    if($error->getRetryInterval() !== null) {
      MailerLog::processNonBlockingError($error->getOperation(), $error->getMessageWithFailedSubscribers(), $error->getRetryInterval());
    } else {
      MailerLog::processError($error->getOperation(), $error->getMessageWithFailedSubscribers());
    }
  }
}
