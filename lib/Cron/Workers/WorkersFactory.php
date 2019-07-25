<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Config\Renderer;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\SendingQueue\Migration as MigrationWorker;
use MailPoet\Cron\Workers\StatsNotifications\AutomatedEmails as StatsNotificationsWorkerForAutomatedEmails;
use MailPoet\Cron\Workers\StatsNotifications\Scheduler as StatsNotificationScheduler;
use MailPoet\Cron\Workers\StatsNotifications\Worker as StatsNotificationsWorker;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck as PremiumKeyCheckWorker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck as SendingServiceKeyCheckWorker;
use MailPoet\Cron\Workers\WooCommerceSync as WooCommerceSyncWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\Mailer\Mailer;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\InactiveSubscribersController;
use MailPoet\WP\Functions as WPFunctions;

class WorkersFactory {

  /** @var SendingErrorHandler */
  private $sending_error_handler;

  /** @var StatsNotificationScheduler */
  private $statsNotificationsScheduler;

  /** @var Mailer */
  private $mailer;

  /** @var SettingsController */
  private $settings;

  /** @var WooCommerceSegment */
  private $woocommerce_segment;

  /** @var InactiveSubscribersController */
  private $inactive_subscribers_controller;

  /** @var WooCommerceHelper */
  private $woocommerce_helper;

  /** @var WooCommercePurchases */
  private $woocommerce_purchases;

  /** @var AuthorizedEmailsController */
  private $authorized_emails_controller;

  /**
   * @var Renderer
   */
  private $renderer;

  /** @var SubscribersFinder */
  private $subscribers_finder;

  public function __construct(
    SendingErrorHandler $sending_error_handler,
    StatsNotificationScheduler $statsNotificationsScheduler,
    Mailer $mailer,
    Renderer $renderer,
    SettingsController $settings,
    WooCommerceSegment $woocommerce_segment,
    InactiveSubscribersController $inactive_subscribers_controller,
    WooCommerceHelper $woocommerce_helper,
    WooCommercePurchases $woocommerce_purchases,
    AuthorizedEmailsController $authorized_emails_controller,
    SubscribersFinder $subscribers_finder
  ) {
    $this->sending_error_handler = $sending_error_handler;
    $this->statsNotificationsScheduler = $statsNotificationsScheduler;
    $this->mailer = $mailer;
    $this->renderer = $renderer;
    $this->settings = $settings;
    $this->woocommerce_segment = $woocommerce_segment;
    $this->inactive_subscribers_controller = $inactive_subscribers_controller;
    $this->woocommerce_helper = $woocommerce_helper;
    $this->woocommerce_purchases = $woocommerce_purchases;
    $this->authorized_emails_controller = $authorized_emails_controller;
    $this->subscribers_finder = $subscribers_finder;
  }

  /** @return SchedulerWorker */
  function createScheduleWorker($timer) {
    return new SchedulerWorker($this->subscribers_finder, $timer);
  }

  /** @return SendingQueueWorker */
  function createQueueWorker($timer) {
    return new SendingQueueWorker($this->sending_error_handler, $this->statsNotificationsScheduler, $timer);
  }

  /** @return StatsNotificationsWorker */
  function createStatsNotificationsWorker($timer) {
    return new StatsNotificationsWorker($this->mailer, $this->renderer, $this->settings, $this->woocommerce_helper, $timer);
  }

  /** @return StatsNotificationsWorkerForAutomatedEmails */
  function createStatsNotificationsWorkerForAutomatedEmails($timer) {
    return new StatsNotificationsWorkerForAutomatedEmails($this->mailer, $this->renderer, $this->settings, $this->woocommerce_helper, $timer);
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

  /** @return Beamer */
  function createBeamerkWorker($timer) {
    return new Beamer($this->settings, WPFunctions::get(), $timer);
  }

  /** @return InactiveSubscribers */
  function createInactiveSubscribersWorker($timer) {
    return new InactiveSubscribers($this->inactive_subscribers_controller, $this->settings, $timer);
  }

    /** @return UnsubscribeTokens */
  function createUnsubscribeTokensWorker($timer) {
    return new UnsubscribeTokens($timer);
  }

  /** @return AuthorizedSendingEmailsCheck */
  function createAuthorizedSendingEmailsCheckWorker($timer) {
    return new AuthorizedSendingEmailsCheck($this->authorized_emails_controller, $timer);
  }

  /** @return WooCommerceOrders */
  function createWooCommerceOrdersWorker($timer) {
    return new WooCommerceOrders($this->woocommerce_helper, $this->woocommerce_purchases, $timer);
  }
}
