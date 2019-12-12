<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Models\Newsletter;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Scheduler {

  static function getNextRunDate($schedule, $from_timestamp = false) {
    $wp = new WPFunctions();
    $from_timestamp = ($from_timestamp) ? $from_timestamp : $wp->currentTime('timestamp');
    try {
      $schedule = \Cron\CronExpression::factory($schedule);
      $next_run_date = $schedule->getNextRunDate(Carbon::createFromTimestamp($from_timestamp))
        ->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
      $next_run_date = false;
    }
    return $next_run_date;
  }

  static function getPreviousRunDate($schedule, $from_timestamp = false) {
    $wp = WPFunctions::get();
    $from_timestamp = ($from_timestamp) ? $from_timestamp : $wp->currentTime('timestamp');
    try {
      $schedule = \Cron\CronExpression::factory($schedule);
      $previous_run_date = $schedule->getPreviousRunDate(Carbon::createFromTimestamp($from_timestamp))
        ->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
      $previous_run_date = false;
    }
    return $previous_run_date;
  }

  static function getScheduledTimeWithDelay($after_time_type, $after_time_number) {
    $wp = WPFunctions::get();
    $current_time = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
    switch ($after_time_type) {
      case 'minutes':
        return $current_time->addMinutes($after_time_number);
      case 'hours':
        return $current_time->addHours($after_time_number);
      case 'days':
        return $current_time->addDays($after_time_number);
      case 'weeks':
        return $current_time->addWeeks($after_time_number);
      default:
        return $current_time;
    }
  }

  static function getNewsletters($type, $group = false) {
    return Newsletter::getPublished()
      ->filter('filterType', $type, $group)
      ->filter('filterStatus', Newsletter::STATUS_ACTIVE)
      ->filter('filterWithOptions', $type)
      ->findMany();
  }

  static function formatDatetimeString($datetime_string) {
    return Carbon::parse($datetime_string)->format('Y-m-d H:i:s');
  }
}
