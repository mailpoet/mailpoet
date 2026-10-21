<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers\KeyCheck;

use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Services\Bridge;
use MailPoetVendor\Carbon\Carbon;

abstract class KeyCheckWorker extends SimpleWorker {
  /** @var Bridge|null */
  public $bridge;

  /** @var CronWorkerScheduler */
  protected $cronWorkerScheduler;

  public function __construct(
    CronWorkerScheduler $cronWorkerScheduler
  ) {
    parent::__construct();
    $this->cronWorkerScheduler = $cronWorkerScheduler;
  }

  public function init() {
    if (!$this->bridge) {
      $this->bridge = new Bridge();
    }
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    try {
      $result = $this->checkKey();
    } catch (\Exception $e) {
      $result = false;
    }

    if (empty($result['code']) || $result['code'] == Bridge::CHECK_ERROR_UNAVAILABLE) {
      $this->cronWorkerScheduler->rescheduleProgressively($task);
      return false;
    }

    return true;
  }

  public function getNextRunDate() {
    $date = Carbon::now()->millisecond(0);
    return $date->startOfDay()
      ->addDay()
      ->addHours(random_int(0, 5))
      ->addMinutes(random_int(0, 59))
      ->addSeconds(random_int(0, 59));
  }

  public abstract function checkKey();
}
