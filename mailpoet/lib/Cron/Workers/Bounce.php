<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\StatisticsBounceEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\StatisticsBouncesRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;

class Bounce extends SimpleWorker {
  const TASK_TYPE = 'bounce';

  // The sending service never reports bounces older than this. Requests with a
  // `from` further back are rejected, so the range is clamped to stay within it.
  const MAX_LOOKBACK_DAYS = 14;

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

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $from = $this->getReportFromDate($task);
    $to = Carbon::now()->millisecond(0);
    $page = 1;

    do {
      // abort if execution limit is reached
      $this->cronHelper->enforceExecutionLimit($timer);

      $report = $this->api->getBouncesReport($from, $to, $page);
      if (!is_array($report)) {
        // Transient failure: leave the task running so it retries with the
        // same range on the next cron tick.
        return false;
      }

      $recipients = isset($report['recipients']) && is_array($report['recipients']) ? $report['recipients'] : [];
      $this->processRecipients($task, $recipients);

      $hasMore = !empty($report['has_more']);
      $page++;
    } while ($hasMore);

    return true;
  }

  public function processRecipients(ScheduledTaskEntity $task, array $recipients): void {
    $previousTask = $this->scheduledTasksRepository->findPreviousTask($task);
    foreach ($recipients as $email) {
      if (!is_string($email) || $email === '') {
        continue;
      }
      $subscriber = $this->subscribersRepository->findOneBy(['email' => $email]);
      if (!$subscriber instanceof SubscriberEntity) {
        continue;
      }
      if (!in_array($subscriber->getStatus(), [SubscriberEntity::STATUS_SUBSCRIBED, SubscriberEntity::STATUS_UNCONFIRMED], true)) {
        continue;
      }
      $subscriber->setStatus(SubscriberEntity::STATUS_BOUNCED);
      $this->saveBouncedStatistics($subscriber, $task, $previousTask);
    }
    $this->subscribersRepository->flush();
  }

  public function getNextRunDate() {
    $date = Carbon::now()->millisecond(0);
    return $date->startOfDay()
      ->addDay()
      ->addHours(rand(0, 5))
      ->addMinutes(rand(0, 59))
      ->addSeconds(rand(0, 59));
  }

  private function getReportFromDate(ScheduledTaskEntity $task): Carbon {
    $now = Carbon::now()->millisecond(0);
    $previousTask = $this->scheduledTasksRepository->findPreviousCompletedTask($task);
    $processedAt = $previousTask instanceof ScheduledTaskEntity ? $previousTask->getProcessedAt() : null;
    $from = $processedAt instanceof \DateTimeInterface
      ? Carbon::instance($processedAt)
      : $now->copy()->subDay();

    // Keep an hour of margin inside MAX_LOOKBACK_DAYS so clock skew and request
    // latency can't push the `from` past the limit the service enforces.
    $earliestAllowed = $now->copy()->subDays(self::MAX_LOOKBACK_DAYS)->addHour();
    return $from->lessThan($earliestAllowed) ? $earliestAllowed : $from;
  }

  private function saveBouncedStatistics(SubscriberEntity $subscriber, ScheduledTaskEntity $task, ?ScheduledTaskEntity $previousTask): void {
    $dateFrom = null;
    if ($previousTask instanceof ScheduledTaskEntity) {
      $dateFrom = $previousTask->getScheduledAt();
    }
    $queues = $this->sendingQueuesRepository->findAllForSubscriberSentBetween($subscriber, $task->getScheduledAt(), $dateFrom);
    foreach ($queues as $queue) {
      $newsletter = $queue->getNewsletter();
      if ($newsletter instanceof NewsletterEntity) {
        $statistics = new StatisticsBounceEntity($newsletter, $queue, $subscriber);
        $this->statisticsBouncesRepository->persist($statistics);
      }
    }
  }
}
