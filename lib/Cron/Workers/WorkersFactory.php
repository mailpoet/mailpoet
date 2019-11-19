<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck as PremiumKeyCheckWorker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck as SendingServiceKeyCheckWorker;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\Migration as MigrationWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\StatsNotifications\AutomatedEmails as StatsNotificationsWorkerForAutomatedEmails;
use MailPoet\Cron\Workers\StatsNotifications\Worker as StatsNotificationsWorker;
use MailPoet\Cron\Workers\WooCommerceSync as WooCommerceSyncWorker;
use MailPoet\DI\ContainerWrapper;

class WorkersFactory {
  /** @var ContainerWrapper */
  private $container;

  public function __construct(ContainerWrapper $container) {
    $this->container = $container;
  }

  /** @return SchedulerWorker */
  function createScheduleWorker() {
    return $this->container->get(SchedulerWorker::class);
  }

  /** @return SendingQueueWorker */
  function createQueueWorker() {
    return $this->container->get(SendingQueueWorker::class);
  }

  /** @return StatsNotificationsWorker */
  function createStatsNotificationsWorker() {
    return $this->container->get(StatsNotificationsWorker::class);
  }

  /** @return StatsNotificationsWorkerForAutomatedEmails */
  function createStatsNotificationsWorkerForAutomatedEmails() {
    return $this->container->get(StatsNotificationsWorkerForAutomatedEmails::class);
  }

  /** @return SendingServiceKeyCheckWorker */
  function createSendingServiceKeyCheckWorker() {
    return $this->container->get(SendingServiceKeyCheckWorker::class);
  }

  /** @return PremiumKeyCheckWorker */
  function createPremiumKeyCheckWorker() {
    return $this->container->get(PremiumKeyCheckWorker::class);
  }

  /** @return BounceWorker */
  function createBounceWorker() {
    return $this->container->get(BounceWorker::class);
  }

  /** @return MigrationWorker */
  function createMigrationWorker() {
    return $this->container->get(MigrationWorker::class);
  }

  /** @return WooCommerceSyncWorker */
  function createWooCommerceSyncWorker() {
    return $this->container->get(WooCommerceSyncWorker::class);
  }

  /** @return ExportFilesCleanup */
  function createExportFilesCleanupWorker() {
    return $this->container->get(ExportFilesCleanup::class);
  }

  /** @return Beamer */
  function createBeamerkWorker() {
    return $this->container->get(Beamer::class);
  }

  /** @return InactiveSubscribers */
  function createInactiveSubscribersWorker() {
    return $this->container->get(InactiveSubscribers::class);
  }

  /** @return UnsubscribeTokens */
  function createUnsubscribeTokensWorker() {
    return $this->container->get(UnsubscribeTokens::class);
  }

  /** @return SubscriberLinkTokens */
  function createSubscriberLinkTokensWorker() {
    return $this->container->get(SubscriberLinkTokens::class);
  }

  /** @return AuthorizedSendingEmailsCheck */
  function createAuthorizedSendingEmailsCheckWorker() {
    return $this->container->get(AuthorizedSendingEmailsCheck::class);
  }

  /** @return WooCommercePastOrders */
  function createWooCommercePastOrdersWorker() {
    return $this->container->get(WooCommercePastOrders::class);
  }
}
