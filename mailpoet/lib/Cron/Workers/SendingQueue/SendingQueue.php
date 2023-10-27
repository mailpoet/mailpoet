<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

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
use MailPoet\InvalidStateException;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\Newsletter;
use MailPoet\Models\StatisticsNewsletters as StatisticsNewslettersModel;
use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Subscribers\BatchIterator;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use Throwable;

class SendingQueue {
  /** @var MailerTask */
  public $mailerTask;

  /** @var NewsletterTask  */
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

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /*** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var EntityManager */
  private $entityManager;

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
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository,
    MailerTask $mailerTask,
    SubscribersRepository $subscribersRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    EntityManager $entityManager,
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
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->entityManager = $entityManager;
  }

  public function process($timer = false) {
    $timer = $timer ?: microtime(true);
    $this->enforceSendingAndExecutionLimits($timer);
    foreach ($this->scheduledTasksRepository->findRunningSendingTasks(self::TASK_BATCH_SIZE) as $task) {
      $queue = $task->getSendingQueue();
      if (!$queue) {
        continue;
      }

      if ($task->getInProgress()) {
        if ($this->isTimeout($task)) {
          $this->stopProgress($task);
        } else {
          continue;
        }
      }


      $this->startProgress($task);

      try {
        $this->scheduledTasksRepository->touchAllByIds([$task->getId()]);
        $this->processSending($task, (int)$timer);
      } catch (\Exception $e) {
        $this->stopProgress($task);
        throw $e;
      }

      $this->stopProgress($task);
    }
  }

  private function processSending(ScheduledTaskEntity $task, int $timer): void {
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
      'sending queue processing',
      ['task_id' => $task->getId()]
    );

    $this->deleteTaskIfNewsletterDoesNotExist($task);

    $queue = $task->getSendingQueue();
    $newsletterEntity = $this->newsletterTask->getNewsletterFromQueue($task);
    if (!$queue || !$newsletterEntity) {
      return;
    }

    // pre-process newsletter (render, replace shortcodes/links, etc.)
    $newsletterEntity = $this->newsletterTask->preProcessNewsletter($newsletterEntity, $task);

    if (!$newsletterEntity) {
      $this->deleteTask($task);
      return;
    }

    $newsletter = Newsletter::findOne($newsletterEntity->getId());
    if (!$newsletter) {
      return;
    }

    $isTransactional = in_array($newsletter->type, [
      NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL,
      NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL,
    ]);

    // clone the original object to be used for processing
    $_newsletter = (object)$newsletter->asArray();
    $_newsletter->options = $newsletterEntity->getOptionsAsArray();
    // configure mailer
    $this->mailerTask->configureMailer($newsletter);
    // get newsletter segments
    $newsletterSegmentsIds = $newsletterEntity->getSegmentIds();
    $segmentIdsToCheck = $newsletterSegmentsIds;
    $filterSegmentId = $newsletterEntity->getFilterSegmentId();

    if (is_int($filterSegmentId)) {
      $segmentIdsToCheck[] = $filterSegmentId;
    }

    // Pause task in case some of related segments was deleted or trashed
    if ($newsletterSegmentsIds && !$this->checkDeletedSegments($segmentIdsToCheck)) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
        'pause task in sending queue due deleted or trashed segment',
        ['task_id' => $task->getId()]
      );
      $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
      $this->scheduledTasksRepository->flush();
      $this->wp->setTransient(self::EMAIL_WITH_INVALID_SEGMENT_OPTION, $newsletter->subject);
      return;
    }

    // get subscribers
    $subscriberBatches = new BatchIterator($task->getId(), $this->getBatchSize());
    if ($subscriberBatches->count() === 0) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
        'no subscribers to process',
        ['task_id' => $task->getId()]
      );
      $this->scheduledTasksRepository->invalidateTask($task);
      return;
    }
    /** @var int[] $subscribersToProcessIds - it's required for PHPStan */
    foreach ($subscriberBatches as $subscribersToProcessIds) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
        'subscriber batch processing',
        ['newsletter_id' => $newsletter->id, 'task_id' => $task->getId(), 'subscriber_batch_count' => count($subscribersToProcessIds)]
      );
      if (!empty($newsletterSegmentsIds[0])) {
        // Check that subscribers are in segments
        try {
          $foundSubscribersIds = $this->subscribersFinder->findSubscribersInSegments($subscribersToProcessIds, $newsletterSegmentsIds, $filterSegmentId);
        } catch (InvalidStateException $exception) {
          $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
            'paused task in sending queue due to problem finding subscribers: ' . $exception->getMessage(),
            ['task_id' => $task->getId()]
          );
          $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
          $this->scheduledTasksRepository->flush();
          return;
        }
        $foundSubscribers = empty($foundSubscribersIds) ? [] : SubscriberModel::whereIn('id', $foundSubscribersIds)
          ->whereNull('deleted_at')
          ->findMany();
      } else {
        // No segments = Welcome emails or some Automatic emails.
        // Welcome emails or some Automatic emails use segments only for scheduling and store them as a newsletter option
        $foundSubscribers = SubscriberModel::whereIn('id', $subscribersToProcessIds);
        $foundSubscribers = $newsletter->type === NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL ?
          $foundSubscribers->whereNotEqual('status', SubscriberModel::STATUS_BOUNCED) :
          $foundSubscribers->where('status', SubscriberModel::STATUS_SUBSCRIBED);
        $foundSubscribers = $foundSubscribers
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

        $this->scheduledTaskSubscribersRepository->deleteByScheduledTaskAndSubscriberIds($task, $subscribersToRemove);
        $this->sendingQueuesRepository->updateCounts($queue);

        if (!$queue->getCountToProcess()) {
          $this->newsletterTask->markNewsletterAsSent($newsletterEntity, $task);
          continue;
        }
        // if there aren't any subscribers to process in batch (e.g. all unsubscribed or were deleted) continue with next batch
        if (count($foundSubscribersIds) === 0) {
          continue;
        }
      }
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
        'before queue chunk processing',
        ['newsletter_id' => $newsletter->id, 'task_id' => $task->getId(), 'found_subscribers_count' => count($foundSubscribers)]
      );

      // reschedule bounce task to run sooner, if needed
      $this->reScheduleBounceTask();

      if ($newsletterEntity->getStatus() !== NewsletterEntity::STATUS_CORRUPT) {
        $this->processQueue(
          $task,
          $_newsletter,
          $foundSubscribers,
          $timer
        );
        if (!$isTransactional) {
          $this->entityManager->wrapInTransaction(function() use ($foundSubscribersIds) {
            $now = Carbon::createFromTimestamp((int)current_time('timestamp'));
            $this->subscribersRepository->bulkUpdateLastSendingAt($foundSubscribersIds, $now);
            // We're nullifying this value so these subscribers' engagement score will be recalculated the next time the cron runs
            $this->subscribersRepository->bulkUpdateEngagementScoreUpdatedAt($foundSubscribersIds, null);
          });
        }
        $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
          'after queue chunk processing',
          ['newsletter_id' => $newsletter->id, 'task_id' => $task->getId()]
        );
        if ($task->getStatus() === ScheduledTaskEntity::STATUS_COMPLETED) {
          $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
            'completed newsletter sending',
            ['newsletter_id' => $newsletter->id, 'task_id' => $task->getId()]
          );
          $this->newsletterTask->markNewsletterAsSent($newsletterEntity, $task);
          $this->statsNotificationsScheduler->schedule($newsletterEntity);
        }
        $this->enforceSendingAndExecutionLimits($timer);
      } else {
        $this->sendingQueuesRepository->pause($queue);
        $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->error(
          'Can\'t send corrupt newsletter',
          ['newsletter_id' => $newsletter->id, 'task_id' => $task->getId()]
        );
      }
    }
  }

  public function getBatchSize(): int {
    return $this->throttlingHandler->getBatchSize();
  }

  public function processQueue(ScheduledTaskEntity $task, $newsletter, $subscribers, $timer) {
    // determine if processing is done in bulk or individually
    $processingMethod = $this->mailerTask->getProcessingMethod();
    $preparedNewsletters = [];
    $preparedSubscribers = [];
    $preparedSubscribersIds = [];
    $unsubscribeUrls = [];
    $statistics = [];
    $metas = [];
    $oneClickUnsubscribeUrls = [];
    $sendingQueueEntity = $task->getSendingQueue();
    if (!$sendingQueueEntity) {
      return;
    }

    $sendingQueueMeta = $sendingQueueEntity->getMeta() ?? [];
    $campaignId = $sendingQueueMeta['campaignId'] ?? null;

    $newsletterEntity = $this->newslettersRepository->findOneById($newsletter->id);
    if (!$newsletterEntity) {
      return;
    }

    foreach ($subscribers as $subscriber) {
      $subscriberEntity = $this->subscribersRepository->findOneById($subscriber->id);

      if (!$subscriberEntity instanceof SubscriberEntity) {
        continue;
      }

      // render shortcodes and replace subscriber data in tracked links
      $preparedNewsletters[] =
        $this->newsletterTask->prepareNewsletterForSending(
          $newsletterEntity,
          $subscriberEntity,
          $sendingQueueEntity
        );
      // format subscriber name/address according to mailer settings
      $preparedSubscribers[] = $this->mailerTask->prepareSubscriberForSending(
        $subscriber
      );
      $preparedSubscribersIds[] = $subscriber->id;
      // create personalized instant unsubsribe link
      $unsubscribeUrls[] = $this->links->getUnsubscribeUrl($sendingQueueEntity->getId(), $subscriberEntity);
      $oneClickUnsubscribeUrls[] = $this->links->getOneClickUnsubscribeUrl($sendingQueueEntity->getId(), $subscriberEntity);

      $metasForSubscriber = $this->mailerMetaInfo->getNewsletterMetaInfo($newsletterEntity, $subscriberEntity);
      if ($campaignId) {
        $metasForSubscriber['campaign_id'] = $campaignId;
      }
      $metas[] = $metasForSubscriber;

      // keep track of values for statistics purposes
      $statistics[] = [
        'newsletter_id' => $newsletter->id,
        'subscriber_id' => $subscriber->id,
        'queue_id' => $sendingQueueEntity->getId(),
      ];
      if ($processingMethod === 'individual') {
        $this->sendNewsletter(
          $task,
          $preparedSubscribersIds[0],
          $preparedNewsletters[0],
          $preparedSubscribers[0],
          $statistics[0],
          $timer,
          [
            'unsubscribe_url' => $unsubscribeUrls[0],
            'meta' => $metas[0],
            'one_click_unsubscribe' => $oneClickUnsubscribeUrls,
          ]
        );
        $preparedNewsletters = [];
        $preparedSubscribers = [];
        $preparedSubscribersIds = [];
        $unsubscribeUrls = [];
        $oneClickUnsubscribeUrls = [];
        $statistics = [];
        $metas = [];
      }
    }
    if ($processingMethod === 'bulk') {
      $this->sendNewsletters(
        $task,
        $preparedSubscribersIds,
        $preparedNewsletters,
        $preparedSubscribers,
        $statistics,
        $timer,
        [
          'unsubscribe_url' => $unsubscribeUrls,
          'meta' => $metas,
          'one_click_unsubscribe' => $oneClickUnsubscribeUrls,
        ]
      );
    }
  }

  public function sendNewsletter(
    ScheduledTaskEntity $task, $preparedSubscriberId, $preparedNewsletter,
    $preparedSubscriber, $statistics, $timer, $extraParams = []
  ) {
    // send newsletter
    $sendResult = $this->mailerTask->send(
      $preparedNewsletter,
      $preparedSubscriber,
      $extraParams
    );
    $this->processSendResult(
      $task,
      $sendResult,
      [$preparedSubscriber],
      [$preparedSubscriberId],
      [$statistics],
      $timer
    );
  }

  public function sendNewsletters(
    ScheduledTaskEntity $task, $preparedSubscribersIds, $preparedNewsletters,
    $preparedSubscribers, $statistics, $timer, $extraParams = []
  ) {
    // send newsletters
    $sendResult = $this->mailerTask->sendBulk(
      $preparedNewsletters,
      $preparedSubscribers,
      $extraParams
    );
    $this->processSendResult(
      $task,
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
    ScheduledTaskEntity $task,
    $sendResult,
    array $preparedSubscribers,
    array $preparedSubscribersIds,
    array $statistics,
    $timer
  ) {
    // log error message and schedule retry/pause sending
    if ($sendResult['response'] === false) {
      $error = $sendResult['error'];
      $this->errorHandler->processError($error, $task, $preparedSubscribersIds, $preparedSubscribers);
    } else {
      $queue = $task->getSendingQueue();
      if (!$queue) {
        return;
      }
      try {
        $this->scheduledTaskSubscribersRepository->updateProcessedSubscribers($task, $preparedSubscribersIds);
        $this->sendingQueuesRepository->updateCounts($queue);
      } catch (Throwable $e) {
        MailerLog::processError(
          'processed_list_update',
          sprintf('QUEUE-%d-PROCESSED-LIST-UPDATE', $queue->getId()),
          null,
          true
        );
      }
    }

    // log statistics
    StatisticsNewslettersModel::createMultiple($statistics);
    // update the sent count
    $this->mailerTask->updateSentCount();
    // enforce execution limits if queue is still being processed
    if ($task->getStatus() !== ScheduledTaskEntity::STATUS_COMPLETED) {
      $this->enforceSendingAndExecutionLimits($timer);
    }
    $this->throttlingHandler->processSuccess();
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

  private function startProgress(ScheduledTaskEntity $task): void {
    $task->setInProgress(true);
    $this->scheduledTasksRepository->flush();
  }

  private function stopProgress(ScheduledTaskEntity $task): void {
    $task->setInProgress(true);
    $this->scheduledTasksRepository->flush();
  }

  private function isTimeout(ScheduledTaskEntity $task): bool {
    $currentTime = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    $updatedAt = new Carbon($task->getUpdatedAt());
    if ($updatedAt->diffInSeconds($currentTime, false) > $this->getExecutionLimit()) {
      return true;
    }

    return false;
  }

  private function getExecutionLimit(): int {
    return $this->cronHelper->getDaemonExecutionLimit() * 3;
  }

  private function deleteTaskIfNewsletterDoesNotExist(ScheduledTaskEntity $task) {
    $queue = $task->getSendingQueue();
    $newsletter = $queue ? $queue->getNewsletter() : null;
    if ($newsletter !== null) {
      return;
    }
    $this->deleteTask($task);
  }

  private function deleteTask(ScheduledTaskEntity $task) {
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
      'delete task in sending queue',
      ['task_id' => $task->getId()]
    );

    $queue = $task->getSendingQueue();
    if ($queue) {
      $this->sendingQueuesRepository->remove($queue);
    }
    $this->scheduledTaskSubscribersRepository->deleteByScheduledTask($task);
    $this->scheduledTasksRepository->remove($task);
    $this->scheduledTasksRepository->flush();
  }
}
