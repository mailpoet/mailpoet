<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;

class UnconfirmedSubscribersCleanup extends SimpleWorker {
  const TASK_TYPE = 'unconfirmed_subscribers_cleanup';
  const RETENTION_DAYS = 30;
  const BATCH_SIZE = 1000;
  const SUPPORT_MULTIPLE_INSTANCES = false;

  /** @var SettingsController */
  private $settings;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    SettingsController $settings,
    SubscribersRepository $subscribersRepository
  ) {
    $this->settings = $settings;
    $this->subscribersRepository = $subscribersRepository;
    parent::__construct();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    if ($this->settings->get('delete_unconfirmed_subscribers_after_days') !== '30') {
      $this->schedule();
      return true;
    }

    $cutoff = Carbon::now()->millisecond(0)->subDays(self::RETENTION_DAYS);

    do {
      $deletedIds = $this->subscribersRepository->deleteUnconfirmedSubscribersForCleanup($cutoff, self::BATCH_SIZE);
      if (count($deletedIds) < self::BATCH_SIZE) {
        break;
      }

      try {
        $this->cronHelper->enforceExecutionLimit($timer);
      } catch (\Exception $e) {
        if ($e->getCode() === CronHelper::DAEMON_EXECUTION_LIMIT_REACHED) {
          $this->cronWorkerScheduler->schedule(static::TASK_TYPE, Carbon::now()->millisecond(0)->addMinutes(5));
        }
        throw $e;
      }
    } while (true);

    $this->schedule();
    return true;
  }

  public function getNextRunDate() {
    return Carbon::now()->millisecond(0)->addDay();
  }
}
