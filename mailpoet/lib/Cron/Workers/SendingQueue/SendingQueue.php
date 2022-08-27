<?php

namespace MailPoet\Cron\Workers\SendingQueue;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\Bounce;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Links;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Cron\Workers\StatsNotifications\Scheduler as StatsNotificationsScheduler;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\StatisticsNewsletters as StatisticsNewslettersModel;
use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Tasks\Subscribers\BatchIterator;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SendingQueue {
  public $mailerTask;
  public $newsletterTask;

  const TASK_TYPE = 'sending';
  const TASK_BATCH_SIZE = 5;
  const EMAIL_WITH_INVALID_SEGMENT_OPTION = 'mailpoet_email_with_invalid_segment';

  /** @var StatsNotificationsScheduler */
  public $statsNotificationsScheduler;

  /** @var SendingErrorHandler */
  private $errorHandler;

  /** @var SendingThrottlingHandler */
  private $throttlingHandler;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var CronHelper */
  private $cronHelper;

  /** @var SubscribersFinder */
  private $subscribersFinder;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var WPFunctions */
  private $wp;

  /** @var Links */
  private $links;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    SendingErrorHandler $errorHandler,
    SendingThrottlingHandler $throttlingHandler,
    StatsNotificationsScheduler $statsNotificationsScheduler,
    LoggerFactory $loggerFactory,
    NewslettersRepository $newslettersRepository,
    CronHelper $cronHelper,
    SubscribersFinder $subscriberFinder,
    SegmentsRepository $segmentsRepository,
    WPFunctions $wp,
    Links $links,
    ScheduledTasksRepository $scheduledTasksRepository,
    MailerTask $mailerTask,
    SubscribersRepository $subscribersRepository,
    $newsletterTask = false
  ) {
    $this->errorHandler = $errorHandler;
    $this->throttlingHandler = $throttlingHandler;
    $this->statsNotificationsScheduler = $statsNotificationsScheduler;
    $this->subscribersFinder = $subscriberFinder;
    $this->mailerTask = $mailerTask;
    $this->newsletterTask = ($newsletterTask) ? $newsletterTask : new NewsletterTask();
    $this->segmentsRepository = $segmentsRepository;
    $this->mailerMetaInfo = new MetaInfo;
    $this->wp = $wp;
    $this->loggerFactory = $loggerFactory;
    $this->newslettersRepository = $newslettersRepository;
    $this->cronHelper = $cronHelper;
    $this->links = $links;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function process($timer = false) {
    $timer = $timer ?: microtime(true);
    $this->enforceSendingAndExecutionLimits($timer);
    foreach ($this->scheduledTasksRepository->findRunningSendingTasks(self::TASK_BATCH_SIZE) as $taskEntity) {
      $task = ScheduledTask::findOne($taskEntity->getId());
      if (!$task instanceof ScheduledTask) continue;

      $queue = SendingTask::createFromScheduledTask($task);
      if (!$queue instanceof SendingTask) continue;

      $task = $queue->task();
      if (!$task instanceof ScheduledTask) continue;

      if ($this->isInProgress($task)) {
        if ($this->isTimeout($task)) {
          $this->stopProgress($task);
        } else {
          continue;
        }
      }


      $this->startProgress($task);

      try {
        $this->scheduledTasksRepository->touchAllByIds([$queue->taskId]);
        $this->processSending($queue, (int)$timer);
      } catch (\Exception $e) {
        $this->stopProgress($task);
        throw $e;
      }

      $this->stopProgress($task);
    }
  }

  private function processSending(SendingTask $queue, int $timer): void {
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
      'sending queue processing',
      ['task_id' => $queue->taskId]
    );

    $newsletter = $this->newsletterTask->getNewsletterFromQueue($queue);
    if (!$newsletter) {
      return;
    }
    $newsletterEntity = $this->newslettersRepository->findOneById($newsletter->id);
    if (!$newsletterEntity) {
      return;
    }

    // pre-process newsletter (render, replace shortcodes/links, etc.)
    $newsletter = $this->newsletterTask->preProcessNewsletter($newsletter, $queue);
    if (!$newsletter) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
        'delete task in sending queue',
        ['task_id' => $queue->taskId]
      );
      $queue->delete();
      return;
    }
    // clone the original object to be used for processing
    $_newsletter = (object)$newsletter->asArray();
    $_newsletter->options = $newsletterEntity->getOptionsAsArray();
    // configure mailer
    $this->mailerTask->configureMailer($newsletter);
    // get newsletter segments
    $newsletterSegmentsIds = $this->newsletterTask->getNewsletterSegments($newsletterEntity);
    // Pause task in case some of related segments was deleted or trashed
    if ($newsletterSegmentsIds && !$this->checkDeletedSegments($newsletterSegmentsIds)) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
        'pause task in sending queue due deleted or trashed segment',
        ['task_id' => $queue->taskId]
      );
      $queue->status = ScheduledTaskEntity::STATUS_PAUSED;
      $queue->save();
      $this->wp->setTransient(self::EMAIL_WITH_INVALID_SEGMENT_OPTION, $newsletter->subject);
      return;
    }

    // get subscribers
    $subscriberBatches = new BatchIterator($queue->taskId, $this->getBatchSize());
    /** @var int[] $subscribersToProcessIds - it's required for PHPStan */
    foreach ($subscriberBatches as $subscribersToProcessIds) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
        'subscriber batch processing',
        ['newsletter_id' => $newsletter->id, 'task_id' => $queue->taskId, 'subscriber_batch_count' => count($subscribersToProcessIds)]
      );
      if (!empty($newsletterSegmentsIds[0])) {
        // Check that subscribers are in segments
        $foundSubscribersIds = $this->subscribersFinder->findSubscribersInSegments($subscribersToProcessIds, $newsletterSegmentsIds);
        $foundSubscribers = empty($foundSubscribersIds) ? [] : SubscriberModel::whereIn('id', $foundSubscribersIds)
          ->whereNull('deleted_at')
          ->findMany();
      } else {
        // No segments = Welcome emails or some Automatic emails.
        // Welcome emails or some Automatic emails use segments only for scheduling and store them as a newsletter option
        $foundSubscribers = SubscriberModel::whereIn('id', $subscribersToProcessIds)
          ->where('status', SubscriberModel::STATUS_SUBSCRIBED)
          ->whereNull('deleted_at')
          ->findMany();
        $foundSubscribersIds = SubscriberModel::extractSubscribersIds($foundSubscribers);
      }
      // if some subscribers weren't found, remove them from the processing list
      if (count($foundSubscribersIds) !== count($subscribersToProcessIds)) {
        $subscribersToRemove = array_diff(
          $subscribersToProcessIds,
          $foundSubscribersIds
        );
        $queue->removeSubscribers($subscribersToRemove);
        if (!$queue->countToProcess) {
          $this->newsletterTask->markNewsletterAsSent($newsletterEntity, $queue);
          continue;
        }
        // if there aren't any subscribers to process in batch (e.g. all unsubscribed or were deleted) continue with next batch
        if (count($foundSubscribersIds) === 0) {
          continue;
        }
      }
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
        'before queue chunk processing',
        ['newsletter_id' => $newsletter->id, 'task_id' => $queue->taskId, 'found_subscribers_count' => count($foundSubscribers)]
      );

      // reschedule bounce task to run sooner, if needed
      $this->reScheduleBounceTask();

      $queue = $this->processQueue(
        $queue,
        $_newsletter,
        $foundSubscribers,
        $timer
      );
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
        'after queue chunk processing',
        ['newsletter_id' => $newsletter->id, 'task_id' => $queue->taskId]
      );
      if ($queue->status === ScheduledTaskEntity::STATUS_COMPLETED) {
        $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
          'completed newsletter sending',
          ['newsletter_id' => $newsletter->id, 'task_id' => $queue->taskId]
        );
        $newsletter = $this->newslettersRepository->findOneById($newsletter->id);
        assert($newsletter instanceof NewsletterEntity);
        $this->newsletterTask->markNewsletterAsSent($newsletter, $queue);
        $this->statsNotificationsScheduler->schedule($newsletter);
      }
      $this->enforceSendingAndExecutionLimits($timer);
    }
  }

  public function getBatchSize(): int {
    return $this->throttlingHandler->getBatchSize();
  }

  public function processQueue($queue, $newsletter, $subscribers, $timer) {
    // determine if processing is done in bulk or individually
    $processingMethod = $this->mailerTask->getProcessingMethod();
    $preparedNewsletters = [];
    $preparedSubscribers = [];
    $preparedSubscribersIds = [];
    $unsubscribeUrls = [];
    $statistics = [];
    $metas = [];
    foreach ($subscribers as $subscriber) {
      $subscriberEntity = $this->subscribersRepository->findOneById($subscriber->id);

      if (!$subscriberEntity instanceof SubscriberEntity) {
        continue;
      }

      // render shortcodes and replace subscriber data in tracked links
      $preparedNewsletters[] =
        $this->newsletterTask->prepareNewsletterForSending(
          $newsletter,
          $subscriberEntity,
          $queue
        );
      // format subscriber name/address according to mailer settings
      $preparedSubscribers[] = $this->mailerTask->prepareSubscriberForSending(
        $subscriber
      );
      $preparedSubscribersIds[] = $subscriber->id;
      // create personalized instant unsubsribe link
      $unsubscribeUrls[] = $this->links->getUnsubscribeUrl($queue, $subscriber->id);

      $metas[] = $this->mailerMetaInfo->getNewsletterMetaInfo($newsletter, $subscriberEntity);

      // keep track of values for statistics purposes
      $statistics[] = [
        'newsletter_id' => $newsletter->id,
        'subscriber_id' => $subscriber->id,
        'queue_id' => $queue->id,
      ];
      if ($processingMethod === 'individual') {
        $queue = $this->sendNewsletter(
          $queue,
          $preparedSubscribersIds[0],
          $preparedNewsletters[0],
          $preparedSubscribers[0],
          $statistics[0],
          $timer,
          ['unsubscribe_url' => $unsubscribeUrls[0], 'meta' => $metas[0]]
        );
        $preparedNewsletters = [];
        $preparedSubscribers = [];
        $preparedSubscribersIds = [];
        $unsubscribeUrls = [];
        $statistics = [];
        $metas = [];
      }
    }
    if ($processingMethod === 'bulk') {
      $queue = $this->sendNewsletters(
        $queue,
        $preparedSubscribersIds,
        $preparedNewsletters,
        $preparedSubscribers,
        $statistics,
        $timer,
        ['unsubscribe_url' => $unsubscribeUrls, 'meta' => $metas]
      );
    }
    return $queue;
  }

  public function sendNewsletter(
    SendingTask $sendingTask, $preparedSubscriberId, $preparedNewsletter,
    $preparedSubscriber, $statistics, $timer, $extraParams = []
  ) {
    // send newsletter
    $sendResult = $this->mailerTask->send(
      $preparedNewsletter,
      $preparedSubscriber,
      $extraParams
    );
    return $this->processSendResult(
      $sendingTask,
      $sendResult,
      [$preparedSubscriber],
      [$preparedSubscriberId],
      [$statistics],
      $timer
    );
  }

  public function sendNewsletters(
    SendingTask $sendingTask, $preparedSubscribersIds, $preparedNewsletters,
    $preparedSubscribers, $statistics, $timer, $extraParams = []
  ) {
    // send newsletters
    $sendResult = $this->mailerTask->sendBulk(
      $preparedNewsletters,
      $preparedSubscribers,
      $extraParams
    );
    return $this->processSendResult(
      $sendingTask,
      $sendResult,
      $preparedSubscribers,
      $preparedSubscribersIds,
      $statistics,
      $timer
    );
  }

  /**
   * Checks whether some of segments was deleted or trashed
   * @param int[] $segmentIds
   */
  private function checkDeletedSegments(array $segmentIds): bool {
    if (count($segmentIds) === 0) {
      return true;
    }
    $segmentIds = array_unique($segmentIds);
    $segments = $this->segmentsRepository->findBy(['id' => $segmentIds]);
    // Some segment was deleted from DB
    if (count($segmentIds) > count($segments)) {
      return false;
    }
    foreach ($segments as $segment) {
      if ($segment->getDeletedAt() !== null) {
        return false;
      }
    }
    return true;
  }

  private function processSendResult(
    SendingTask $sendingTask,
    $sendResult,
    array $preparedSubscribers,
    array $preparedSubscribersIds,
    array $statistics,
    $timer
  ) {
    // log error message and schedule retry/pause sending
    if ($sendResult['response'] === false) {
      $error = $sendResult['error'];
      assert($error instanceof MailerError);
      $this->errorHandler->processError($error, $sendingTask, $preparedSubscribersIds, $preparedSubscribers);
    } elseif (!$sendingTask->updateProcessedSubscribers($preparedSubscribersIds)) { // update processed/to process list
      MailerLog::processError(
        'processed_list_update',
        sprintf('QUEUE-%d-PROCESSED-LIST-UPDATE', $sendingTask->id),
        null,
        true
      );
    }
    // log statistics
    StatisticsNewslettersModel::createMultiple($statistics);
    // update the sent count
    $this->mailerTask->updateSentCount();
    // enforce execution limits if queue is still being processed
    if ($sendingTask->status !== ScheduledTaskEntity::STATUS_COMPLETED) {
      $this->enforceSendingAndExecutionLimits($timer);
    }
    $this->throttlingHandler->processSuccess();
    return $sendingTask;
  }

  public function enforceSendingAndExecutionLimits($timer) {
    // abort if execution limit is reached
    $this->cronHelper->enforceExecutionLimit($timer);
    // abort if sending limit has been reached
    MailerLog::enforceExecutionRequirements();
  }

  private function reScheduleBounceTask() {
    $bounceTasks = $this->scheduledTasksRepository->findFutureScheduledByType(Bounce::TASK_TYPE);
    if (count($bounceTasks)) {
      $bounceTask = reset($bounceTasks);
      if (Carbon::createFromTimestamp((int)current_time('timestamp'))->addHours(42)->lessThan($bounceTask->getScheduledAt())) {
        $randomOffset = rand(-6 * 60 * 60, 6 * 60 * 60);
        $bounceTask->setScheduledAt(Carbon::createFromTimestamp((int)current_time('timestamp'))->addSeconds((36 * 60 * 60) + $randomOffset));
        $this->scheduledTasksRepository->persist($bounceTask);
        $this->scheduledTasksRepository->flush();
      }
    }
  }

  private function isInProgress(ScheduledTask $task): bool {
    if (!empty($task->inProgress)) {
      // Do not run multiple instances of the task
      return true;
    }
    return false;
  }

  private function startProgress(ScheduledTask $task): void {
    $task->inProgress = true;
    $task->save();
  }

  private function stopProgress(ScheduledTask $task): void {
    $task->inProgress = false;
    $task->save();
  }

  private function isTimeout(ScheduledTask $task): bool {
    $currentTime = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    $updated = strtotime((string)$task->updatedAt);
    if ($updated !== false) {
      $updatedAt = Carbon::createFromTimestamp($updated);
    }
    if (isset($updatedAt) && $updatedAt->diffInSeconds($currentTime, false) > $this->getExecutionLimit()) {
      return true;
    }

    return false;
  }

  private function getExecutionLimit(): int {
    return $this->cronHelper->getDaemonExecutionLimit() * 3;
  }
}
