<?php
namespace MailPoet\WP;

class DateTime {

  const INTERNAL_DATE_FORMAT = 'Y-m-d';
  const INTERNAL_TIME_FORMAT = 'H:i:s';
  const INTERNAL_DATE_TIME_FORMAT = 'Y-m-d H:i:s';

  function __construct() {
  }

  function getTimeFormat() {
    $time_format = get_option('time_format');
    if (empty($time_format)) $time_format = self::INTERNAL_TIME_FORMAT;
    return $time_format;
  }

  function getDateFormat() {
    $date_format = get_option('date_format');
    if (empty($date_format)) $date_format = self::INTERNAL_DATE_FORMAT;
    return $date_format;
  }

  function getCurrentTime($format=false) {
    if (empty($format)) $format = $this->getTimeFormat();
    return current_time($format);
  }

  function getCurrentDate($format=false) {
    if (empty($format)) $format = $this->getDateFormat();
    return $this->getCurrentTime($format);
  }

  function getTime($time, $format=false) {
    if (empty($format)) $format = $this->getTimeFormat();

    return date($format, $time);
  }

  /**
   * Generates a list of time strings within an interval,
   * formatted and mapped from INTERNAL_TIME_FORMAT to Wordpress time strings.
   */
  function getTimeInterval(
    $start_time='00:00:00',
    $time_step='+1 hour',
    $total_steps=24
  ) {
    $steps = array();

    $internal_time = $start_time;
    $timestamp = strtotime($internal_time);

    for ($step = 0; $step < $total_steps; $step += 1) {
      $wordpress_time = $this->getTime($timestamp);
      $steps[$internal_time] = $wordpress_time;

      $timestamp = strtotime($time_step, $timestamp);
      $internal_time = $this->getTime($timestamp, self::INTERNAL_TIME_FORMAT);
    }

    return $steps;
  }
}
