<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\InactiveSubscribersController;
use MailPoet\Subscribers\SubscribersEmailCountsController;
use MailPoet\Subscribers\SubscribersRepository;

class InactiveSubscribersMaintenance extends SimpleWorker {
  const TASK_TYPE = 'inactive_subscribers_maintenance';
  const BATCH_SIZE = 1000;
  const SUPPORT_MULTIPLE_INSTANCES = false;
  const LEGACY_EMAIL_COUNT_TASK_TYPE = 'subscribers_email_count';

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

    $dateFromLastRun = $this->getDateFromLastRun($task);
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

    while ($this->inactiveSubscribersController->markActiveSubscribers($daysToInactive, self::BATCH_SIZE) === self::BATCH_SIZE) {
      $this->cronHelper->enforceExecutionLimit($timer);
    }

    $this->schedule();
    return true;
  }

  private function getDateFromLastRun(ScheduledTaskEntity $task): ?\DateTimeInterface {
    $previousTask = $this->scheduledTasksRepository->findPreviousTask($task);
    if (!($previousTask instanceof ScheduledTaskEntity)) {
      $previousTask = $this->scheduledTasksRepository->findOneBy([
        'type' => self::LEGACY_EMAIL_COUNT_TASK_TYPE,
        'status' => ScheduledTaskEntity::STATUS_COMPLETED,
        'deletedAt' => null,
      ], ['scheduledAt' => 'DESC']);
    }
    return $previousTask instanceof ScheduledTaskEntity ? $previousTask->getScheduledAt() : null;
  }
}
