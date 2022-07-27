<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\InactiveSubscribersController;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class InactiveSubscribers extends SimpleWorker {
  const TASK_TYPE = 'inactive_subscribers';
  const BATCH_SIZE = 1000;
  const SUPPORT_MULTIPLE_INSTANCES = false;

  /** @var InactiveSubscribersController */
  private $inactiveSubscribersController;

  /** @var SettingsController */
  private $settings;

  /** @var TrackingConfig */
  private $trackingConfig;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    InactiveSubscribersController $inactiveSubscribersController,
    SettingsController $settings,
    TrackingConfig $trackingConfig,
    EntityManager $entityManager
  ) {
    $this->inactiveSubscribersController = $inactiveSubscribersController;
    $this->settings = $settings;
    $this->trackingConfig = $trackingConfig;
    $this->entityManager = $entityManager;
    parent::__construct();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    if (!$this->trackingConfig->isEmailTrackingEnabled()) {
      $this->schedule();
      return true;
    }
    $daysToInactive = (int)$this->settings->get('deactivate_subscriber_after_inactive_days');
    // Activate all inactive subscribers in case the feature is turned off
    if ($daysToInactive === 0) {
      $this->inactiveSubscribersController->reactivateInactiveSubscribers();
      $this->schedule();
      return true;
    }
    // Handle activation/deactivation within interval
    $meta = $task->getMeta();
    $lastSubscriberId = isset($meta['last_subscriber_id']) ? $meta['last_subscriber_id'] : 0;

    if (isset($meta['max_subscriber_id'])) {
      $maxSubscriberId = $meta['max_subscriber_id'];
    } else {
      $maxSubscriberId = $this->entityManager->createQueryBuilder()
        ->select('MAX(s.id)')
        ->from(SubscriberEntity::class, 's')
        ->getQuery()
        ->getSingleScalarResult();
    }

    while ($lastSubscriberId <= $maxSubscriberId) {
      $count = $this->inactiveSubscribersController->markInactiveSubscribers($daysToInactive, self::BATCH_SIZE, $lastSubscriberId);
      if ($count === false) {
        break;
      }
      $lastSubscriberId += self::BATCH_SIZE;
      $task->setMeta(['last_subscriber_id' => $lastSubscriberId]);
      $this->scheduledTasksRepository->persist($task);
      $this->scheduledTasksRepository->flush();
      $this->cronHelper->enforceExecutionLimit($timer);
    };
    while ($this->inactiveSubscribersController->markActiveSubscribers($daysToInactive, self::BATCH_SIZE) === self::BATCH_SIZE) {
      $this->cronHelper->enforceExecutionLimit($timer);
    };
    $this->schedule();
    return true;
  }
}
