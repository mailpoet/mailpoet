<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\StatsNotificationEntity;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class Scheduler {

  /**
   * How many hours after the newsletter will be the stats notification sent
   * @var int
   */
  const HOURS_TO_SEND_AFTER_NEWSLETTER = 24;

  /** @var SettingsController */
  private $settings;

  private $supported_types = [
    NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
    NewsletterEntity::TYPE_STANDARD,
  ];

  /** @var EntityManager */
  private $entity_manager;

  /** @var StatsNotificationsRepository */
  private $repository;

  public function __construct(
    SettingsController $settings,
    EntityManager $entity_manager,
    StatsNotificationsRepository $repository
  ) {
    $this->settings = $settings;
    $this->entity_manager = $entity_manager;
    $this->repository = $repository;
  }

  public function schedule(NewsletterEntity $newsletter) {
    if (!$this->shouldSchedule($newsletter)) {
      return false;
    }

    $task = new ScheduledTaskEntity();
    $task->setType(Worker::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setScheduledAt($this->getNextRunDate());
    $this->entity_manager->persist($task);
    $this->entity_manager->flush();

    $stats_notifications = new StatsNotificationEntity($newsletter, $task);
    $this->entity_manager->persist($stats_notifications);
    $this->entity_manager->flush();
  }

  private function shouldSchedule(NewsletterEntity $newsletter) {
    if ($this->isDisabled()) {
      return false;
    }
    if (!in_array($newsletter->getType(), $this->supported_types)) {
      return false;
    }
    if ($this->hasTaskBeenScheduled($newsletter->getId())) {
      return false;
    }
    return true;
  }

  private function isDisabled() {
    $settings = $this->settings->get(Worker::SETTINGS_KEY);
    if (!is_array($settings)) {
      return true;
    }
    if (!isset($settings['enabled'])) {
      return true;
    }
    if (!isset($settings['address'])) {
      return true;
    }
    if (empty(trim($settings['address']))) {
      return true;
    }
    if (!(bool)$this->settings->get('tracking.enabled')) {
      return true;
    }
    return !(bool)$settings['enabled'];
  }

  private function hasTaskBeenScheduled($newsletter_id) {
    $existing = $this->repository->findOneByNewsletterId($newsletter_id);
    return $existing instanceof StatsNotificationEntity;
  }

  private function getNextRunDate() {
    $date = new Carbon();
    $date->addHours(self::HOURS_TO_SEND_AFTER_NEWSLETTER);
    return $date;
  }

}
