<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Config\Renderer;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\SendingQueue\Migration as MigrationWorker;
use MailPoet\Cron\Workers\StatsNotifications\Worker as StatsNotificationsWorker;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck as PremiumKeyCheckWorker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck as SendingServiceKeyCheckWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;

class WorkersFactory {

  /** @var SendingErrorHandler */
  private $sending_error_handler;

  public function __construct(SendingErrorHandler $sending_error_handler) {
    $this->sending_error_handler = $sending_error_handler;
  }

  /** @return SchedulerWorker */
  function createScheduleWorker($timer) {
    return new SchedulerWorker($timer);
  }

  /** @return SendingQueueWorker */
  function createQueueWorker($timer) {
    return new SendingQueueWorker($this->sending_error_handler, $this->createStatsNotificationsWorker($timer), $timer);
  }

  function createStatsNotificationsWorker($timer) {
    //TODO get those from DI
    $caching = !WP_DEBUG;
    $debugging = WP_DEBUG;
    $this->renderer = new Renderer($caching, $debugging);
    $this->mailer = new \MailPoet\Mailer\Mailer();

    return new StatsNotificationsWorker($this->mailer, $this->renderer, $timer);
  }

  /** @return SendingServiceKeyCheckWorker */
  function createSendingServiceKeyCheckWorker($timer) {
    return new SendingServiceKeyCheckWorker($timer);
  }

  /** @return PremiumKeyCheckWorker */
  function createPremiumKeyCheckWorker($timer) {
    return new PremiumKeyCheckWorker($timer);
  }

  /** @return BounceWorker */
  function createBounceWorker($timer) {
    return new BounceWorker($timer);
  }

  /** @return MigrationWorker */
  function createMigrationWorker($timer) {
    return new MigrationWorker($timer);
  }

}
