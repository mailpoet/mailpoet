<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\StatisticsBounceEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\StatisticsBouncesRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Bounce as BounceTask;
use MailPoet\Tasks\Subscribers as TaskSubscribers;
use MailPoet\Tasks\Subscribers\BatchIterator;
use MailPoetVendor\Carbon\Carbon;

class Bounce extends SimpleWorker {
  const TASK_TYPE = 'bounce';
  const BATCH_SIZE = 100;

  const BOUNCED_HARD = 'hard';
  const BOUNCED_SOFT = 'soft';
  const NOT_BOUNCED = null;

  public $api;

  /** @var SettingsController */
  private $settings;

  /** @var Bridge */
  private $bridge;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var StatisticsBouncesRepository */
  private $statisticsBouncesRepository;

  public function __construct(
    SettingsController $settings,
    SubscribersRepository $subscribersRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    StatisticsBouncesRepository $statisticsBouncesRepository,
    Bridge $bridge
  ) {
    $this->settings = $settings;
    $this->bridge = $bridge;
    parent::__construct();
    $this->subscribersRepository = $subscribersRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->statisticsBouncesRepository = $statisticsBouncesRepository;
  }

  public function init() {
    if (!$this->api) {
      $this->api = new API($this->settings->get(Mailer::MAILER_CONFIG_SETTING_NAME)['mailpoet_api_key']);
    }
  }

  public function checkProcessingRequirements() {
    return $this->bridge->isMailpoetSendingServiceEnabled();
  }

  public function prepareTaskStrategy(ScheduledTaskEntity $task, $timer) {
    BounceTask::prepareSubscribers($task);

    if (!ScheduledTaskSubscriber::getUnprocessedCount($task->getId())) {
      ScheduledTaskSubscriber::where('task_id', $task->getId())->deleteMany();
      return false;
    }
    return true;
  }

  public function processTaskStrategy(ScheduledTask $task, $timer) {
    $subscriberBatches = new BatchIterator($task->id, self::BATCH_SIZE);

    if (count($subscriberBatches) === 0) {
      ScheduledTaskSubscriber::where('task_id', $task->id)->deleteMany();
      return true; // mark completed
    }

    $taskSubscribers = new TaskSubscribers($task);

    foreach ($subscriberBatches as $subscribersToProcessIds) {
      // abort if execution limit is reached
      $this->cronHelper->enforceExecutionLimit($timer);

      $subscriberEmails = $this->subscribersRepository->getUndeletedSubscribersEmailsByIds($subscribersToProcessIds);
      $subscriberEmails = array_column($subscriberEmails, 'email');

      $this->processEmails($task, $subscriberEmails);

      $taskSubscribers->updateProcessedSubscribers($subscribersToProcessIds);
    }

    return true;
  }

  public function processEmails($task, array $subscriberEmails) {
    $checkedEmails = $this->api->checkBounces($subscriberEmails);
    $this->processApiResponse($task, (array)$checkedEmails);
  }

  public function processApiResponse($task, array $checkedEmails) {
    $previousTask = $this->findPreviousTask($task);
    foreach ($checkedEmails as $email) {
      if (!isset($email['address'], $email['bounce'])) {
        continue;
      }
      if ($email['bounce'] === self::BOUNCED_HARD) {
        $subscriber = $this->subscribersRepository->findOneBy(['email' => $email['address']]);
        if (!$subscriber instanceof SubscriberEntity) continue;
        $subscriber->setStatus(SubscriberEntity::STATUS_BOUNCED);
        $this->saveBouncedStatistics($subscriber, $task, $previousTask);
      }
    }
    $this->subscribersRepository->flush();
  }

  public function getNextRunDate() {
    $date = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    return $date->startOfDay()
      ->addDay()
      ->addHours(rand(0, 5))
      ->addMinutes(rand(0, 59))
      ->addSeconds(rand(0, 59));
  }

  private function findPreviousTask(ScheduledTask $task): ?ScheduledTaskEntity {
    $taskEntity = $this->scheduledTasksRepository->findOneById($task->id);
    if (!$taskEntity instanceof ScheduledTaskEntity) return null;
    return $this->scheduledTasksRepository->findPreviousTask($taskEntity);
  }

  private function saveBouncedStatistics(SubscriberEntity $subscriber, ScheduledTask $task, ?ScheduledTaskEntity $previousTask): void {
    $taskEntity = $this->scheduledTasksRepository->findOneById($task->id);
    if (!$taskEntity instanceof ScheduledTaskEntity) return;
    $dateFrom = null;
    if ($previousTask instanceof ScheduledTaskEntity) {
      $dateFrom = $previousTask->getScheduledAt();
    }
    $queues = $this->sendingQueuesRepository->findAllForSubscriberSentBetween($subscriber, $taskEntity->getScheduledAt(), $dateFrom);
    foreach ($queues as $queue) {
      $newsletter = $queue->getNewsletter();
      if ($newsletter instanceof NewsletterEntity) {
        $statistics = new StatisticsBounceEntity($newsletter, $queue, $subscriber);
        $this->statisticsBouncesRepository->persist($statistics);
      }
    }
  }
}
