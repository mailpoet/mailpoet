<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Config\Renderer;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck as PremiumKeyCheckWorker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck as SendingServiceKeyCheckWorker;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\Migration as MigrationWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\StatsNotifications\AutomatedEmails as StatsNotificationsWorkerForAutomatedEmails;
use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\Cron\Workers\StatsNotifications\Scheduler as StatsNotificationScheduler;
use MailPoet\Cron\Workers\StatsNotifications\StatsNotificationsRepository;
use MailPoet\Cron\Workers\StatsNotifications\Worker as StatsNotificationsWorker;
use MailPoet\Cron\Workers\WooCommerceSync as WooCommerceSyncWorker;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\Subscribers\InactiveSubscribersController;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\EntityManager;

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

  /** @var Renderer */
  private $renderer;

  /** @var SubscribersFinder */
  private $subscribers_finder;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var LoggerFactory */
  private $logger_factory;

  /** @var StatsNotificationsRepository */
  private $stats_notifications_repository;

  /** @var EntityManager */
  private $entity_manager;

  /**
   * @var NewslettersRepository
   */
  private $newsletters_repository;

  /** @var NewsletterLinkRepository */
  private $newsletter_link_repository;

  /** @var NewsletterStatisticsRepository */
  private $newsletter_statistics_repository;

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
    SubscribersFinder $subscribers_finder,
    MetaInfo $mailerMetaInfo,
    LoggerFactory $logger_factory,
    StatsNotificationsRepository $stats_notifications_repository,
    NewslettersRepository $newsletters_repository,
    NewsletterLinkRepository $newsletter_link_repository,
    NewsletterStatisticsRepository $newsletter_statistics_repository,
    EntityManager $entity_manager
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
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->logger_factory = $logger_factory;
    $this->stats_notifications_repository = $stats_notifications_repository;
    $this->entity_manager = $entity_manager;
    $this->newsletters_repository = $newsletters_repository;
    $this->newsletter_link_repository = $newsletter_link_repository;
    $this->newsletter_statistics_repository = $newsletter_statistics_repository;
  }

  /** @return SchedulerWorker */
  function createScheduleWorker($timer) {
    return new SchedulerWorker($this->subscribers_finder, $this->logger_factory, $timer);
  }

  /** @return SendingQueueWorker */
  function createQueueWorker($timer) {
    return new SendingQueueWorker(
      $this->sending_error_handler,
      $this->statsNotificationsScheduler,
      $this->logger_factory,
      $this->newsletters_repository,
      $timer
    );
  }

  /** @return StatsNotificationsWorker */
  function createStatsNotificationsWorker($timer) {
    return new StatsNotificationsWorker(
      $this->mailer,
      $this->renderer,
      $this->settings,
      $this->mailerMetaInfo,
      $this->stats_notifications_repository,
      $this->newsletter_link_repository,
      $this->newsletter_statistics_repository,
      $this->entity_manager,
      $timer
    );
  }

  /** @return StatsNotificationsWorkerForAutomatedEmails */
  function createStatsNotificationsWorkerForAutomatedEmails($timer) {
    return new StatsNotificationsWorkerForAutomatedEmails($this->mailer, $this->renderer, $this->settings, $this->woocommerce_helper, $this->mailerMetaInfo, $timer);
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

   /** @return SubscriberLinkTokens */
  function createSubscriberLinkTokensWorker($timer) {
    return new SubscriberLinkTokens($timer);
  }

  /** @return AuthorizedSendingEmailsCheck */
  function createAuthorizedSendingEmailsCheckWorker($timer) {
    return new AuthorizedSendingEmailsCheck($this->authorized_emails_controller, $timer);
  }

  /** @return WooCommercePastOrders */
  function createWooCommercePastOrdersWorker($timer) {
    return new WooCommercePastOrders($this->woocommerce_helper, $this->woocommerce_purchases, $timer);
  }
}
