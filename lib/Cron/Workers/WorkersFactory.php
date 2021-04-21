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
  public function createScheduleWorker() {
    return $this->container->get(SchedulerWorker::class);
  }

  /** @return SendingQueueWorker */
  public function createQueueWorker() {
    return $this->container->get(SendingQueueWorker::class);
  }

  /** @return StatsNotificationsWorker */
  public function createStatsNotificationsWorker() {
    return $this->container->get(StatsNotificationsWorker::class);
  }

  /** @return StatsNotificationsWorkerForAutomatedEmails */
  public function createStatsNotificationsWorkerForAutomatedEmails() {
    return $this->container->get(StatsNotificationsWorkerForAutomatedEmails::class);
  }

  /** @return SendingServiceKeyCheckWorker */
  public function createSendingServiceKeyCheckWorker() {
    return $this->container->get(SendingServiceKeyCheckWorker::class);
  }

  /** @return PremiumKeyCheckWorker */
  public function createPremiumKeyCheckWorker() {
    return $this->container->get(PremiumKeyCheckWorker::class);
  }

  /** @return BounceWorker */
  public function createBounceWorker() {
    return $this->container->get(BounceWorker::class);
  }

  /** @return MigrationWorker */
  public function createMigrationWorker() {
    return $this->container->get(MigrationWorker::class);
  }

  /** @return WooCommerceSyncWorker */
  public function createWooCommerceSyncWorker() {
    return $this->container->get(WooCommerceSyncWorker::class);
  }

  /** @return ExportFilesCleanup */
  public function createExportFilesCleanupWorker() {
    return $this->container->get(ExportFilesCleanup::class);
  }

  /** @return Beamer */
  public function createBeamerkWorker() {
    return $this->container->get(Beamer::class);
  }

  /** @return InactiveSubscribers */
  public function createInactiveSubscribersWorker() {
    return $this->container->get(InactiveSubscribers::class);
  }

  /** @return UnsubscribeTokens */
  public function createUnsubscribeTokensWorker() {
    return $this->container->get(UnsubscribeTokens::class);
  }

  /** @return SubscriberLinkTokens */
  public function createSubscriberLinkTokensWorker() {
    return $this->container->get(SubscriberLinkTokens::class);
  }

  /** @return SubscribersEngagementScore */
  public function createSubscribersEngagementScoreWorker() {
    return $this->container->get(SubscribersEngagementScore::class);
  }

  /** @return AuthorizedSendingEmailsCheck */
  public function createAuthorizedSendingEmailsCheckWorker() {
    return $this->container->get(AuthorizedSendingEmailsCheck::class);
  }

  /** @return WooCommercePastOrders */
  public function createWooCommercePastOrdersWorker() {
    return $this->container->get(WooCommercePastOrders::class);
  }
}
