<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Models\Newsletter;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Scheduler {
  public static function getNextRunDate($schedule, $fromTimestamp = false) {
    $wp = new WPFunctions();
    $fromTimestamp = ($fromTimestamp) ? $fromTimestamp : $wp->currentTime('timestamp');
    try {
      $schedule = \Cron\CronExpression::factory($schedule);
      $nextRunDate = $schedule->getNextRunDate(Carbon::createFromTimestamp($fromTimestamp))
        ->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
      $nextRunDate = false;
    }
    return $nextRunDate;
  }

  public static function getPreviousRunDate($schedule, $fromTimestamp = false) {
    $wp = WPFunctions::get();
    $fromTimestamp = ($fromTimestamp) ? $fromTimestamp : $wp->currentTime('timestamp');
    try {
      $schedule = \Cron\CronExpression::factory($schedule);
      $previousRunDate = $schedule->getPreviousRunDate(Carbon::createFromTimestamp($fromTimestamp))
        ->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
      $previousRunDate = false;
    }
    return $previousRunDate;
  }

  public static function getScheduledTimeWithDelay($afterTimeType, $afterTimeNumber, $wp = null): Carbon {
    $wp = $wp ?? WPFunctions::get();
    $currentTime = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
    switch ($afterTimeType) {
      case 'minutes':
        return $currentTime->addMinutes($afterTimeNumber);
      case 'hours':
        return $currentTime->addHours($afterTimeNumber);
      case 'days':
        return $currentTime->addDays($afterTimeNumber);
      case 'weeks':
        return $currentTime->addWeeks($afterTimeNumber);
      default:
        return $currentTime;
    }
  }

  public static function getNewsletters($type, $group = false) {
    return Newsletter::getPublished()
      ->filter('filterType', $type, $group)
      ->filter('filterStatus', Newsletter::STATUS_ACTIVE)
      ->filter('filterWithOptions', $type)
      ->findMany();
  }

  public static function formatDatetimeString($datetimeString) {
    return Carbon::parse($datetimeString)->format('Y-m-d H:i:s');
  }
}
