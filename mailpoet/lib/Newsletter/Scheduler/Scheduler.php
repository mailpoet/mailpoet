<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Models\Newsletter;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Scheduler {
  const MYSQL_TIMESTAMP_MAX = '2038-01-19 03:14:07';

  /** @var WPFunctions  */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function getNextRunDate($schedule, $fromTimestamp = false) {
    $fromTimestamp = ($fromTimestamp) ? $fromTimestamp : $this->wp->currentTime('timestamp');
    try {
      $schedule = \Cron\CronExpression::factory($schedule);
      $nextRunDate = $schedule->getNextRunDate(Carbon::createFromTimestamp($fromTimestamp))
        ->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
      $nextRunDate = false;
    }
    return $nextRunDate;
  }

  public function getPreviousRunDate($schedule, $fromTimestamp = false) {
    $fromTimestamp = ($fromTimestamp) ? $fromTimestamp : $this->wp->currentTime('timestamp');
    try {
      $schedule = \Cron\CronExpression::factory($schedule);
      $previousRunDate = $schedule->getPreviousRunDate(Carbon::createFromTimestamp($fromTimestamp))
        ->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
      $previousRunDate = false;
    }
    return $previousRunDate;
  }

  public function getScheduledTimeWithDelay($afterTimeType, $afterTimeNumber): Carbon {
    $currentTime = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    switch ($afterTimeType) {
      case 'minutes':
        $currentTime->addMinutes($afterTimeNumber);
        break;
      case 'hours':
        $currentTime->addHours($afterTimeNumber);
        break;
      case 'days':
        $currentTime->addDays($afterTimeNumber);
        break;
      case 'weeks':
        $currentTime->addWeeks($afterTimeNumber);
        break;
    }
    $maxScheduledTime = Carbon::createFromFormat('Y-m-d H:i:s', self::MYSQL_TIMESTAMP_MAX);
    if ($maxScheduledTime && $currentTime > $maxScheduledTime) {
      return $maxScheduledTime;
    }
    return $currentTime;
  }

  public function getNewsletters($type, $group = false) {
    return Newsletter::getPublished()
      ->filter('filterType', $type, $group)
      ->filter('filterStatus', Newsletter::STATUS_ACTIVE)
      ->filter('filterWithOptions', $type)
      ->findMany();
  }

  public function formatDatetimeString($datetimeString) {
    return Carbon::parse($datetimeString)->format('Y-m-d H:i:s');
  }
}
