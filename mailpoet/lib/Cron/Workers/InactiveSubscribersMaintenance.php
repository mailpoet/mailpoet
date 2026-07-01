<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\InactiveSubscribersController;
use MailPoet\Subscribers\SubscribersEmailCountsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class InactiveSubscribersMaintenance extends SimpleWorker {
  const TASK_TYPE = 'inactive_subscribers_maintenance';
  const BATCH_SIZE = 1000;
  const SUPPORT_MULTIPLE_INSTANCES = false;
  const LAST_EMAIL_COUNT_AT_SETTING = 'inactive_subscribers_maintenance_last_email_count_at';

  /** @var SubscribersEmailCountsController */
  private $subscribersEmailCountsController;

  /** @var InactiveSubscribersController */
  private $inactiveSubscribersController;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SettingsController */
  private $settings;

  /** @var TrackingConfig */
  private $trackingConfig;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    SubscribersEmailCountsController $subscribersEmailCountsController,
    InactiveSubscribersController $inactiveSubscribersController,
    SubscribersRepository $subscribersRepository,
    SettingsController $settings,
    TrackingConfig $trackingConfig,
    EntityManager $entityManager
  ) {
    $this->subscribersEmailCountsController = $subscribersEmailCountsController;
    $this->inactiveSubscribersController = $inactiveSubscribersController;
    $this->subscribersRepository = $subscribersRepository;
    $this->settings = $settings;
    $this->trackingConfig = $trackingConfig;
    $this->entityManager = $entityManager;
    parent::__construct();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $meta = $task->getMeta();
    $meta = is_array($meta) ? $meta : [];

    // A partially counted run persists its cursor and frozen cutoff but writes the baseline only
    // when it finishes. If the feature is disabled before it resumes, the early-return below
    // completes the task with counting half-done. Drop the baseline in that case so the next
    // enabled run does a full recount (which resets email_count) instead of incrementing the
    // already-counted windows again.
    $hasPartialEmailCountProgress = isset($meta['last_subscriber_id'], $meta['email_count_cutoff']);

    if (!$this->trackingConfig->isEmailTrackingEnabled()) {
      if ($hasPartialEmailCountProgress) {
        $this->settings->delete(self::LAST_EMAIL_COUNT_AT_SETTING);
      }
      $this->schedule();
      return true;
    }

    $daysToInactive = (int)$this->settings->get('deactivate_subscriber_after_inactive_days');
    if ($daysToInactive === 0) {
      if ($hasPartialEmailCountProgress) {
        $this->settings->delete(self::LAST_EMAIL_COUNT_AT_SETTING);
      }
      $this->inactiveSubscribersController->reactivateInactiveSubscribers();
      $this->schedule();
      return true;
    }

    // Freeze one cutoff for the whole run (persisted so resumes reuse it) so every window
    // counts up to the same point and the stored baseline matches it exactly. Otherwise late
    // cron (scheduled_at != now) or a multi-tick run would re-count or skip a sliver of tasks.
    $countCutoff = isset($meta['email_count_cutoff']) && is_string($meta['email_count_cutoff'])
      ? Carbon::parse($meta['email_count_cutoff'])
      : new Carbon();
    $meta['email_count_cutoff'] = $countCutoff->format(\DateTimeInterface::ATOM);

    $dateFromLastRun = $this->getDateFromLastRun();
    $refreshCounts = $dateFromLastRun === null || $this->subscribersEmailCountsController->hasNewSendingTasksSince($dateFromLastRun, $countCutoff);

    $startId = isset($meta['last_subscriber_id']) ? (int)$meta['last_subscriber_id'] : 0;

    while (true) {
      [$count, $endId] = $this->subscribersRepository->getNextIdWindow($startId, self::BATCH_SIZE);
      if ($count === 0) {
        break;
      }

      // Count and advance the cursor atomically: the count increment is not idempotent, so a
      // failure must not leave the count applied while the cursor still points at this window.
      $this->entityManager->wrapInTransaction(function() use ($refreshCounts, $dateFromLastRun, $startId, $endId, $countCutoff, $task, &$meta): void {
        if ($refreshCounts) {
          $this->subscribersEmailCountsController->updateSubscribersEmailCounts($dateFromLastRun, $startId, $endId, $countCutoff);
        }
        $meta['last_subscriber_id'] = $endId + 1;
        $task->setMeta($meta);
        $this->scheduledTasksRepository->persist($task);
        $this->scheduledTasksRepository->flush();
      });

      // Marking is idempotent (recomputed from current state each pass), so it stays outside the
      // transaction -- a marking failure cannot trigger a re-count of an already-counted window.
      $this->inactiveSubscribersController->markInactiveSubscribers($daysToInactive, $startId, $endId);
      $this->cronHelper->enforceExecutionLimit($timer);
      $startId = $endId + 1;
    }

    // Baseline = the frozen cutoff, matching exactly what was counted, so the next run resumes
    // from the right point even if this run's cron was late or spanned several ticks.
    $this->rememberLastEmailCountDate($countCutoff);

    while ($this->inactiveSubscribersController->markActiveSubscribers($daysToInactive, self::BATCH_SIZE) === self::BATCH_SIZE) {
      $this->cronHelper->enforceExecutionLimit($timer);
    }

    $this->schedule();
    return true;
  }

  private function getDateFromLastRun(): ?\DateTimeInterface {
    // Baseline is the last run that actually counted, not the last completed task -- runs
    // while inactivity detection is off complete without counting and must not move it. On
    // upgrade the migration seeds this setting from the last legacy email-count task.
    $lastEmailCountAt = $this->settings->get(self::LAST_EMAIL_COUNT_AT_SETTING);
    return is_string($lastEmailCountAt) && $lastEmailCountAt !== '' ? Carbon::parse($lastEmailCountAt) : null;
  }

  private function rememberLastEmailCountDate(\DateTimeInterface $countCutoff): void {
    $this->settings->set(self::LAST_EMAIL_COUNT_AT_SETTING, $countCutoff->format(\DateTimeInterface::ATOM));
  }
}
