<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers;

use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\StatisticsBounceEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Services\Bridge\BouncesReportException;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\StatisticsBouncesRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;

class Bounce extends SimpleWorker {
  const TASK_TYPE = 'bounce';
  const SUPPORT_MULTIPLE_INSTANCES = false;

  // The sending service never reports bounces older than this. Requests with a
  // `from` further back are rejected, so the range is clamped to stay within it.
  const MAX_LOOKBACK_DAYS = 14;

  // Stores the `to` of the last fully-processed report so the next daily run
  // starts its range exactly there, giving gap-free coverage without querying
  // previous tasks.
  const LAST_REPORT_TO_SETTING_KEY = 'bounce.last_report_to';

  // Keys under which the in-progress report range and pagination cursor are
  // persisted on the task meta, so a run that hits the execution limit resumes
  // the same range from the next page instead of restarting at page 1.
  const META_FROM = 'report_from';
  const META_TO = 'report_to';
  const META_PAGE = 'report_page';

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

  /** @var ServicesChecker */
  private $servicesChecker;

  public function __construct(
    SettingsController $settings,
    SubscribersRepository $subscribersRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    StatisticsBouncesRepository $statisticsBouncesRepository,
    Bridge $bridge,
    ServicesChecker $servicesChecker
  ) {
    $this->settings = $settings;
    $this->bridge = $bridge;
    parent::__construct();
    $this->subscribersRepository = $subscribersRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->statisticsBouncesRepository = $statisticsBouncesRepository;
    $this->servicesChecker = $servicesChecker;
  }

  public function init() {
    if (!$this->api) {
      $this->api = new API($this->settings->get(Mailer::MAILER_CONFIG_SETTING_NAME)['mailpoet_api_key']);
    }
  }

  public function checkProcessingRequirements() {
    // A key the service rejects can never produce a report, so stop the worker
    // instead of requesting one on every cron tick. SendingServiceKeyCheck owns
    // this state and flips it back once the key works again, which re-enables
    // the worker without any bounce-specific recovery path.
    return $this->bridge->isMailpoetSendingServiceEnabled()
      && $this->servicesChecker->isMailPoetAPIKeyValid(false) === true;
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    [$from, $to] = $this->getReportRange($task);
    $page = $this->getReportPage($task);

    do {
      // abort if execution limit is reached
      $this->cronHelper->enforceExecutionLimit($timer);

      try {
        $report = $this->api->getBouncesReport($from, $to, $page);
      } catch (BouncesReportException $e) {
        return $this->handleReportFailure($task, $e);
      }

      $recipients = isset($report['recipients']) && is_array($report['recipients']) ? $report['recipients'] : [];
      $this->processRecipients($task, $recipients);

      $hasMore = !empty($report['has_more']);
      $page++;
      // Persist the cursor so a subsequent execution-limit timeout resumes from
      // the next unprocessed page instead of replaying the whole range.
      $this->saveReportPage($task, $page);
    } while ($hasMore);

    // The whole range is consumed; record its `to` as the basis for the next
    // daily run's `from` so coverage stays continuous.
    $existing = $this->settings->get(self::LAST_REPORT_TO_SETTING_KEY);
    $existingTo = null;
    if (is_string($existing) && $existing !== '') {
      try {
        $existingTo = Carbon::parse($existing);
      } catch (\Exception $e) {
        $existingTo = null;
      }
    }
    if (!$existingTo || $to->greaterThan($existingTo)) {
      $this->settings->set(self::LAST_REPORT_TO_SETTING_KEY, $to->format(\DateTimeInterface::ATOM));
    }
    return true;
  }

  /**
   * Backs the task off instead of letting it retry on the very next cron tick.
   * The report range is frozen on the task meta, so the delayed retry still
   * covers exactly the same window.
   */
  private function handleReportFailure(ScheduledTaskEntity $task, BouncesReportException $e): bool {
    $code = $e->getCode();
    if ($code === API::RESPONSE_CODE_KEY_INVALID || $code === API::RESPONSE_CODE_CAN_NOT_SEND) {
      // The report endpoint rejected the key, but it is not the authority on key
      // state: it is registered on WPCOM, while keys are issued and validated by
      // bridge.mailpoet.com. So ask the authority to re-check now rather than
      // waiting up to a day for the scheduled check, and let the key state it
      // stores decide (via checkProcessingRequirements) whether this worker keeps
      // running. Writing the state from here instead would let a WPCOM-side fault
      // pause all sending on evidence from a service that does not issue keys.
      $this->cronWorkerScheduler->scheduleImmediatelyIfNotRunning(SendingServiceKeyCheck::TASK_TYPE);
    }
    $this->cronWorkerScheduler->rescheduleProgressively($task);
    return false;
  }

  /**
   * @return array{0: Carbon, 1: Carbon}
   */
  private function getReportRange(ScheduledTaskEntity $task): array {
    $meta = $task->getMeta();
    if (is_array($meta) && isset($meta[self::META_FROM], $meta[self::META_TO])) {
      return [Carbon::parse($meta[self::META_FROM]), Carbon::parse($meta[self::META_TO])];
    }

    $to = Carbon::now()->millisecond(0);
    $from = $this->getReportFromDate($to);

    $meta = is_array($meta) ? $meta : [];
    $meta[self::META_FROM] = $from->format(\DateTimeInterface::ATOM);
    $meta[self::META_TO] = $to->format(\DateTimeInterface::ATOM);
    $meta[self::META_PAGE] = $meta[self::META_PAGE] ?? 1;
    $task->setMeta($meta);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    return [$from, $to];
  }

  private function getReportPage(ScheduledTaskEntity $task): int {
    $meta = $task->getMeta();
    $page = is_array($meta) && isset($meta[self::META_PAGE]) ? (int)$meta[self::META_PAGE] : 1;
    return $page > 0 ? $page : 1;
  }

  private function saveReportPage(ScheduledTaskEntity $task, int $page): void {
    $meta = $task->getMeta();
    $meta = is_array($meta) ? $meta : [];
    $meta[self::META_PAGE] = $page;
    $task->setMeta($meta);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }

  public function processRecipients(ScheduledTaskEntity $task, array $recipients): void {
    $emails = array_values(array_unique(array_filter(
      $recipients,
      function ($email): bool {
        return is_string($email) && $email !== '';
      }
    )));
    if (empty($emails)) {
      return;
    }

    // Only subscribers currently subscribed/unconfirmed transition to bounced,
    // preserving prior behavior. Loading them in one query (instead of one
    // lookup per recipient) is the batching this task needed; the status change
    // itself stays on the managed entities so the Doctrine lifecycle listeners
    // (status-change notifications, subscriber counts) still fire.
    $subscribers = $this->subscribersRepository->findBy([
      'email' => $emails,
      'status' => [SubscriberEntity::STATUS_SUBSCRIBED, SubscriberEntity::STATUS_UNCONFIRMED],
      'deletedAt' => null,
    ]);
    if (empty($subscribers)) {
      return;
    }

    $previousTask = $this->scheduledTasksRepository->findPreviousTask($task);
    foreach ($subscribers as $subscriber) {
      $subscriber->setStatus(SubscriberEntity::STATUS_BOUNCED);
      $this->saveBouncedStatistics($subscriber, $task, $previousTask);
    }
    // A single flush commits the status changes and the new statistics together
    // in one transaction, so a failure cannot record statistics without the
    // matching status change. A replayed page is then a no-op: the subscribers
    // are already bounced and fall outside the status filter above.
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

  private function getReportFromDate(Carbon $now): Carbon {
    $lastReportTo = $this->settings->get(self::LAST_REPORT_TO_SETTING_KEY);
    // A malformed stored value (corruption, manual edit, older code) would make
    // Carbon::parse throw and crash the worker on every run, so guard it the
    // same way the persist path above does and fall back to the default `from`.
    $from = $now->copy()->subDay();
    if (is_string($lastReportTo) && $lastReportTo !== '') {
      try {
        $from = Carbon::parse($lastReportTo);
      } catch (\Exception $e) {
        $from = $now->copy()->subDay();
      }
    }

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
