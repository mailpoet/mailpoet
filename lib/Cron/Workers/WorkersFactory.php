<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Config\Renderer;
use MailPoet\Cron\Workers\StatsNotifications\Scheduler as StatsNotificationScheduler;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\SendingQueue\Migration as MigrationWorker;
use MailPoet\Cron\Workers\StatsNotifications\Worker as StatsNotificationsWorker;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck as PremiumKeyCheckWorker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck as SendingServiceKeyCheckWorker;
use MailPoet\Cron\Workers\WooCommerceSync as WooCommerceSyncWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\Mailer\Mailer;
use MailPoet\Settings\SettingsController;

class WorkersFactory {

  /** @var SendingErrorHandler */
  private $sending_error_handler;

  /** @var StatsNotificationScheduler */
  private $scheduler;

  /** @var Mailer */
  private $mailer;

  /** @var SettingsController */
  private $settings;

  /** @var WooCommerceSegment */
  private $woocommerce_segment;

  /**
   * @var Renderer
   */
  private $renderer;

  public function __construct(
    SendingErrorHandler $sending_error_handler,
    StatsNotificationScheduler $scheduler,
    Mailer $mailer,
    Renderer $renderer,
    SettingsController $settings,
    WooCommerceSegment $woocommerce_segment
  ) {
    $this->sending_error_handler = $sending_error_handler;
    $this->scheduler = $scheduler;
    $this->mailer = $mailer;
    $this->renderer = $renderer;
    $this->settings = $settings;
    $this->woocommerce_segment = $woocommerce_segment;
  }

  /** @return SchedulerWorker */
  function createScheduleWorker($timer) {
    return new SchedulerWorker($timer);
  }

  /** @return SendingQueueWorker */
  function createQueueWorker($timer) {
    return new SendingQueueWorker($this->sending_error_handler, $this->scheduler, $timer);
  }

  function createStatsNotificationsWorker($timer) {
    return new StatsNotificationsWorker($this->mailer, $this->renderer, $this->settings, $timer);
  }

  /** @return SendingServiceKeyCheckWorker */
  function createSendingServiceKeyCheckWorker($timer) {
    return new SendingServiceKeyCheckWorker($timer);
  }

  /** @return PremiumKeyCheckWorker */
  function createPremiumKeyCheckWorker($timer) {
    return new PremiumKeyCheckWorker($this->settings, $timer);
  }

  /** @return BounceWorker */
  function createBounceWorker($timer) {
    return new BounceWorker($timer);
  }

  /** @return MigrationWorker */
  function createMigrationWorker($timer) {
    return new MigrationWorker($timer);
  }

  /** @return WooCommerceSyncWorker */
  function createWooCommerceSyncWorker($timer) {
    return new WooCommerceSyncWorker($this->woocommerce_segment, $timer);
  }

}
