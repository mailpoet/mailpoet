<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\InactiveSubscribersController;
use MailPoet\Subscribers\SubscribersEmailCountsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;

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

  public function __construct(
    SubscribersEmailCountsController $subscribersEmailCountsController,
    InactiveSubscribersController $inactiveSubscribersController,
    SubscribersRepository $subscribersRepository,
    SettingsController $settings,
    TrackingConfig $trackingConfig
  ) {
    $this->subscribersEmailCountsController = $subscribersEmailCountsController;
    $this->inactiveSubscribersController = $inactiveSubscribersController;
    $this->subscribersRepository = $subscribersRepository;
    $this->settings = $settings;
    $this->trackingConfig = $trackingConfig;
    parent::__construct();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    if (!$this->trackingConfig->isEmailTrackingEnabled()) {
      $this->schedule();
      return true;
    }

    $daysToInactive = (int)$this->settings->get('deactivate_subscriber_after_inactive_days');
    if ($daysToInactive === 0) {
      $this->inactiveSubscribersController->reactivateInactiveSubscribers();
      $this->schedule();
      return true;
    }

    $dateFromLastRun = $this->getDateFromLastRun();
    $refreshCounts = $dateFromLastRun === null || $this->subscribersEmailCountsController->hasNewSendingTasksSince($dateFromLastRun);

    $meta = $task->getMeta();
    $meta = is_array($meta) ? $meta : [];
    $startId = isset($meta['last_subscriber_id']) ? (int)$meta['last_subscriber_id'] : 0;

    while (true) {
      [$count, $endId] = $this->subscribersRepository->getNextIdWindow($startId, self::BATCH_SIZE);
      if ($count === 0) {
        break;
      }

      if ($refreshCounts) {
        $this->subscribersEmailCountsController->updateSubscribersEmailCounts($dateFromLastRun, $startId, $endId);
      }
      $this->inactiveSubscribersController->markInactiveSubscribers($daysToInactive, $startId, $endId);

      $startId = $endId + 1;
      $meta['last_subscriber_id'] = $startId;
      $task->setMeta($meta);
      $this->scheduledTasksRepository->persist($task);
      $this->scheduledTasksRepository->flush();
      $this->cronHelper->enforceExecutionLimit($timer);
    }

    // Persist the point up to which email counts are now current. The next run counts
    // incrementally from here, so a stretch with inactivity detection off (which skips
    // counting) cannot leave an uncounted gap.
    $this->rememberLastEmailCountDate($task);

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

  private function rememberLastEmailCountDate(ScheduledTaskEntity $task): void {
    $scheduledAt = $task->getScheduledAt();
    $date = $scheduledAt instanceof \DateTimeInterface ? $scheduledAt : new Carbon();
    $this->settings->set(self::LAST_EMAIL_COUNT_AT_SETTING, $date->format(\DateTimeInterface::ATOM));
  }
}
