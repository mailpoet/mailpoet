<?php

namespace MailPoet\Cron\Workers\SendingQueue;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\Bounce;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Links;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Cron\Workers\StatsNotifications\Scheduler as StatsNotificationsScheduler;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTask as ScheduledTaskModel;
use MailPoet\Models\StatisticsNewsletters as StatisticsNewslettersModel;
use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Tasks\Subscribers\BatchIterator;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SendingQueue {
  public $mailerTask;
  public $newsletterTask;
  public $batchSize;
  const BATCH_SIZE = 20;
  const TASK_BATCH_SIZE = 5;

  /** @var StatsNotificationsScheduler */
  public $statsNotificationsScheduler;

  /** @var SendingErrorHandler */
  private $errorHandler;

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

  public function __construct(
    SendingErrorHandler $errorHandler,
    StatsNotificationsScheduler $statsNotificationsScheduler,
    LoggerFactory $loggerFactory,
    NewslettersRepository $newslettersRepository,
    CronHelper $cronHelper,
    SubscribersFinder $subscriberFinder,
    $mailerTask = false,
    $newsletterTask = false
  ) {
    $this->errorHandler = $errorHandler;
    $this->statsNotificationsScheduler = $statsNotificationsScheduler;
    $this->subscribersFinder = $subscriberFinder;
    $this->mailerTask = ($mailerTask) ? $mailerTask : new MailerTask();
    $this->newsletterTask = ($newsletterTask) ? $newsletterTask : new NewsletterTask();
    $this->mailerMetaInfo = new MetaInfo;
    $wp = new WPFunctions;
    $this->batchSize = $wp->applyFilters('mailpoet_cron_worker_sending_queue_batch_size', self::BATCH_SIZE);
    $this->loggerFactory = $loggerFactory;
    $this->newslettersRepository = $newslettersRepository;
    $this->cronHelper = $cronHelper;
  }

  public function process($timer = false) {
    $timer = $timer ?: microtime(true);
    $this->enforceSendingAndExecutionLimits($timer);
    foreach (self::getRunningQueues() as $queue) {
      if (!$queue instanceof SendingTask) continue;
      ScheduledTaskModel::touchAllByIds([$queue->taskId]);

      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->addInfo(
        'sending queue processing',
        ['task_id' => $queue->taskId]
      );
      $newsletter = $this->newsletterTask->getNewsletterFromQueue($queue);
      if (!$newsletter) {
        continue;
      }
      // pre-process newsletter (render, replace shortcodes/links, etc.)
      $newsletter = $this->newsletterTask->preProcessNewsletter($newsletter, $queue);
      if (!$newsletter) {
        $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->addInfo(
          'delete task in sending queue',
          ['task_id' => $queue->taskId]
        );
        $queue->delete();
        continue;
      }
      // clone the original object to be used for processing
      $_newsletter = (object)$newsletter->asArray();
      $options = $newsletter->options()->findMany();
      if (!empty($options)) {
        $options = array_column($options, 'value', 'name');
      }
      $_newsletter->options = $options;
      // configure mailer
      $this->mailerTask->configureMailer($newsletter);
      // get newsletter segments
      $newsletterSegmentsIds = $this->newsletterTask->getNewsletterSegments($newsletter);
      // get subscribers
      $subscriberBatches = new BatchIterator($queue->taskId, $this->batchSize);
      foreach ($subscriberBatches as $subscribersToProcessIds) {
        $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->addInfo(
          'subscriber batch processing',
          ['newsletter_id' => $newsletter->id, 'task_id' => $queue->taskId, 'subscriber_batch_count' => count($subscribersToProcessIds)]
        );
        if (!empty($newsletterSegmentsIds[0])) {
          // Check that subscribers are in segments
          $foundSubscribersIds = $this->subscribersFinder->findSubscribersInSegments($subscribersToProcessIds, $newsletterSegmentsIds);
          $foundSubscribers = SubscriberModel::whereIn('id', $subscribersToProcessIds)
            ->whereNull('deleted_at')
            ->findMany();
        } else {
          // No segments = Welcome emails
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
            $this->newsletterTask->markNewsletterAsSent($newsletter, $queue);
            continue;
          }
        }
        $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->addInfo(
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
        $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->addInfo(
          'after queue chunk processing',
          ['newsletter_id' => $newsletter->id, 'task_id' => $queue->taskId]
        );
        if ($queue->status === ScheduledTaskModel::STATUS_COMPLETED) {
          $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->addInfo(
            'completed newsletter sending',
            ['newsletter_id' => $newsletter->id, 'task_id' => $queue->taskId]
          );
          $this->newsletterTask->markNewsletterAsSent($newsletter, $queue);
          $newsletter = $this->newslettersRepository->findOneById($newsletter->id);
          assert($newsletter instanceof NewsletterEntity);
          $this->statsNotificationsScheduler->schedule($newsletter);
        }
        $this->enforceSendingAndExecutionLimits($timer);
      }
    }
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
      // render shortcodes and replace subscriber data in tracked links
      $preparedNewsletters[] =
        $this->newsletterTask->prepareNewsletterForSending(
          $newsletter,
          $subscriber,
          $queue
        );
      // format subscriber name/address according to mailer settings
      $preparedSubscribers[] = $this->mailerTask->prepareSubscriberForSending(
        $subscriber
      );
      $preparedSubscribersIds[] = $subscriber->id;
      // create personalized instant unsubsribe link
      $unsubscribeUrls[] = Links::getUnsubscribeUrl($queue, $subscriber->id);
      $metas[] = $this->mailerMetaInfo->getNewsletterMetaInfo($newsletter, $subscriber);
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
    }
    // update processed/to process list
    if (!$sendingTask->updateProcessedSubscribers($preparedSubscribersIds)) {
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
    if ($sendingTask->status !== ScheduledTaskModel::STATUS_COMPLETED) {
      $this->enforceSendingAndExecutionLimits($timer);
    }
    return $sendingTask;
  }

  public function enforceSendingAndExecutionLimits($timer) {
    // abort if execution limit is reached
    $this->cronHelper->enforceExecutionLimit($timer);
    // abort if sending limit has been reached
    MailerLog::enforceExecutionRequirements();
  }

  public static function getRunningQueues() {
    return SendingTask::getRunningQueues(self::TASK_BATCH_SIZE);
  }

  private function reScheduleBounceTask() {
    $bounceTasks = ScheduledTask::findFutureScheduledByType(Bounce::TASK_TYPE);
    if (count($bounceTasks)) {
      $bounceTask = reset($bounceTasks);
      if (Carbon::createFromTimestamp((int)current_time('timestamp'))->addHours(42)->lessThan($bounceTask->scheduledAt)) {
        $randomOffset = rand(-6 * 60 * 60, 6 * 60 * 60);
        $bounceTask->scheduledAt = Carbon::createFromTimestamp((int)current_time('timestamp'))->addSeconds((36 * 60 * 60) + $randomOffset);
        $bounceTask->save();
      }
    }
  }
}
