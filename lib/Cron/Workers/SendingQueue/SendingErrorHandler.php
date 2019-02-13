<?php
namespace MailPoet\Cron\Workers\SendingQueue;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Tasks\Sending as SendingTask;

class SendingErrorHandler {
  function processError(
    MailerError $error,
    SendingTask $sending_task,
    array $prepared_subscribers_ids,
    array $prepared_subscribers
  ) {
    if ($error->getLevel() === MailerError::LEVEL_HARD) {
      return $this->processHardError($error);
    }
    $this->processSoftError($error, $sending_task, $prepared_subscribers_ids, $prepared_subscribers);
  }

  private function processHardError(MailerError $error) {
    if ($error->getRetryInterval() !== null) {
      MailerLog::processNonBlockingError($error->getOperation(), $error->getMessageWithFailedSubscribers(), $error->getRetryInterval());
    } else {
      MailerLog::processError($error->getOperation(), $error->getMessageWithFailedSubscribers());
    }
  }

  private function processSoftError(MailerError $error, SendingTask $sending_task, $prepared_subscribers_ids, $prepared_subscribers) {
    foreach ($error->getSubscriberErrors() as $subscriber_error) {
      $subscriber_id_index = array_search($subscriber_error->getEmail(), $prepared_subscribers);
      $message = $subscriber_error->getMessage() ?: $error->getMessage();
      $sending_task->saveSubscriberError($prepared_subscribers_ids[$subscriber_id_index], $message);
    }
  }
}
