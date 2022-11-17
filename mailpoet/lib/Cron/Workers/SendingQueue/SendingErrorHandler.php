<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers\SendingQueue;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Tasks\Sending as SendingTask;

class SendingErrorHandler {
  /** @var SendingThrottlingHandler */
  private $throttlingHandler;

  public function __construct(
    SendingThrottlingHandler $throttlingHandler
  ) {
    $this->throttlingHandler = $throttlingHandler;
  }

  public function processError(
    MailerError $error,
    SendingTask $sendingTask,
    array $preparedSubscribersIds,
    array $preparedSubscribers
  ) {
    if ($error->getLevel() === MailerError::LEVEL_HARD) {
      return $this->processHardError($error);
    }
    $this->processSoftError($error, $sendingTask, $preparedSubscribersIds, $preparedSubscribers);
  }

  private function processHardError(MailerError $error) {
    if ($error->getRetryInterval() !== null) {
      MailerLog::processNonBlockingError($error->getOperation(), $error->getMessageWithFailedSubscribers(), $error->getRetryInterval());
    } else {
      $throttledBatchSize = null;
      if ($error->getOperation() === MailerError::OPERATION_CONNECT) {
        $throttledBatchSize = $this->throttlingHandler->throttleBatchSize();
      }
      MailerLog::processError($error->getOperation(), $error->getMessageWithFailedSubscribers(), null, false, $throttledBatchSize);
    }
  }

  private function processSoftError(MailerError $error, SendingTask $sendingTask, $preparedSubscribersIds, $preparedSubscribers) {
    foreach ($error->getSubscriberErrors() as $subscriberError) {
      $subscriberIdIndex = array_search($subscriberError->getEmail(), $preparedSubscribers);
      $message = $subscriberError->getMessage() ?: $error->getMessage();
      $sendingTask->saveSubscriberError($preparedSubscribersIds[$subscriberIdIndex], $message);
    }
  }
}
