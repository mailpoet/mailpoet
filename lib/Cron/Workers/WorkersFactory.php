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
use MailPoet\Features\FeaturesController;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\Mailer\Mailer;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\InactiveSubscribersController;

class WorkersFactory {

  /** @var SendingErrorHandler */
  private $sending_error_handler;

  /** @var StatsNotificationScheduler */
  private $scheduler;

  /** @var Mailer */
  private $mailer;

  /** @var SettingsController */
  private $settings;

  /** @var FeaturesController */
  private $features_controller;

  /** @var WooCommerceSegment */
  private $woocommerce_segment;

  /** @var InactiveSubscribersController */
  private $inactive_subscribers_controller;

  /** @var WooCommerceHelper */
  private $woocommerce_helper;

  /** @var AuthorizedEmailsController */
  private $authorized_emails_controller;

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
    FeaturesController $features_controller,
    WooCommerceSegment $woocommerce_segment,
    InactiveSubscribersController $inactive_subscribers_controller,
    WooCommerceHelper $woocommerce_helper,
    AuthorizedEmailsController $authorized_emails_controller
  ) {
    $this->sending_error_handler = $sending_error_handler;
    $this->scheduler = $scheduler;
    $this->mailer = $mailer;
    $this->renderer = $renderer;
    $this->settings = $settings;
    $this->features_controller = $features_controller;
    $this->woocommerce_segment = $woocommerce_segment;
    $this->inactive_subscribers_controller = $inactive_subscribers_controller;
    $this->woocommerce_helper = $woocommerce_helper;
    $this->authorized_emails_controller = $authorized_emails_controller;
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
    return new StatsNotificationsWorker($this->mailer, $this->renderer, $this->settings, $this->features_controller, $this->woocommerce_helper, $timer);
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
    return new WooCommerceSyncWorker($this->woocommerce_segment, $this->woocommerce_helper, $timer);
  }

  /** @return ExportFilesCleanup */
  function createExportFilesCleanupWorker($timer) {
    return new ExportFilesCleanup($timer);
  }

  /** @return InactiveSubscribers */
  function createInactiveSubscribersWorker($timer) {
    return new InactiveSubscribers($this->inactive_subscribers_controller, $this->settings, $timer);
  }

  /** @return AuthorizedSendingEmailsCheck */
  function createAuthorizedSendingEmailsCheckWorker($timer) {
    return new AuthorizedSendingEmailsCheck($this->authorized_emails_controller, $timer);
  }

}
