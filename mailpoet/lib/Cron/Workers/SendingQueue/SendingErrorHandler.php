<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers\SendingQueue;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\Sending\ScheduledTaskQueuedSubscriberRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscriberMover;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;

class SendingErrorHandler {
  /** @var ScheduledTaskSubscriberMover */
  private $scheduledTaskSubscriberMover;

  /** @var ScheduledTaskQueuedSubscriberRepository */
  private $scheduledTaskQueuedSubscriberRepository;

  /** @var SendingThrottlingHandler */
  private $throttlingHandler;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var LoggerFactory */
  private $loggerFactory;

  public function __construct(
    ScheduledTaskSubscriberMover $scheduledTaskSubscriberMover,
    ScheduledTaskQueuedSubscriberRepository $scheduledTaskQueuedSubscriberRepository,
    SendingThrottlingHandler $throttlingHandler,
    SendingQueuesRepository $sendingQueuesRepository,
    LoggerFactory $loggerFactory
  ) {
    $this->scheduledTaskSubscriberMover = $scheduledTaskSubscriberMover;
    $this->scheduledTaskQueuedSubscriberRepository = $scheduledTaskQueuedSubscriberRepository;
    $this->throttlingHandler = $throttlingHandler;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->loggerFactory = $loggerFactory;
  }

  public function processError(
    MailerError $error,
    ScheduledTaskEntity $task,
    array $preparedSubscribersIds,
    array $preparedSubscribers
  ) {
    if ($error->getLevel() === MailerError::LEVEL_HARD) {
      return $this->processHardError($error);
    }
    $this->processSoftError($error, $task, $preparedSubscribersIds, $preparedSubscribers);
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

  private function processSoftError(MailerError $error, ScheduledTaskEntity $task, $preparedSubscribersIds, $preparedSubscribers) {
    foreach ($error->getSubscriberErrors() as $subscriberError) {
      $subscriberIdIndex = array_search($subscriberError->getEmail(), $preparedSubscribers, true);
      // An error for an email we didn't send in this batch can't be mapped to a
      // queued recipient; skip it instead of moving the wrong subscriber (index 0) to the failed log.
      if ($subscriberIdIndex === false || !array_key_exists($subscriberIdIndex, $preparedSubscribersIds)) {
        continue;
      }
      $message = $subscriberError->getMessage() ?: $error->getMessage();
      $this->scheduledTaskSubscriberMover->moveFailedToLog($task, $preparedSubscribersIds[$subscriberIdIndex], $message ?? '');
    }
    $this->scheduledTaskQueuedSubscriberRepository->checkCompleted($task);

    $queue = $task->getSendingQueue();

    if ($queue instanceof SendingQueueEntity) {
      if ($error->getOperation() === MailerError::OPERATION_DOMAIN_AUTHORIZATION) {
        $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
          'Paused task in sending queue due to sender domain authorization error',
          ['task_id' => $task->getId()]
        );
        $this->sendingQueuesRepository->pause($queue);
        return;
      }
      $this->sendingQueuesRepository->updateCounts($queue);
    }
  }
}
