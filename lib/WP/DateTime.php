<?php
namespace MailPoet\WP;

use MailPoet\WP\Functions as WPFunctions;

class DateTime {

  const DEFAULT_DATE_FORMAT = 'Y-m-d';
  const DEFAULT_TIME_FORMAT = 'H:i:s';
  const DEFAULT_DATE_TIME_FORMAT = 'Y-m-d H:i:s';

  private $wp;

  function __construct(WPFunctions $wp = null) {
    if ($wp === null) {
      $wp = new WPFunctions();
    }
    $this->wp = $wp;
  }

  function getTimeFormat() {
    $time_format = $this->wp->getOption('time_format');
    if (empty($time_format)) $time_format = self::DEFAULT_TIME_FORMAT;
    return $time_format;
  }

  function getDateFormat() {
    $date_format = $this->wp->getOption('date_format');
    if (empty($date_format)) $date_format = self::DEFAULT_DATE_FORMAT;
    return $date_format;
  }

  function getCurrentTime($format=false) {
    if (empty($format)) $format = $this->getTimeFormat();
    return $this->wp->currentTime($format);
  }

  function getCurrentDate($format=false) {
    if (empty($format)) $format = $this->getDateFormat();
    return $this->getCurrentTime($format);
  }

  function formatTime($timestamp, $format=false) {
    if (empty($format)) $format = $this->getTimeFormat();

    return date($format, $timestamp);
  }

  function formatDate($timestamp, $format=false) {
    if (empty($format)) $format = $this->getDateFormat();

    return date($format, $timestamp);
  }

  /**
   * Generates a list of time strings within an interval,
   * formatted and mapped from DEFAULT_TIME_FORMAT to WordPress time strings.
   */
  function getTimeInterval(
    $start_time='00:00:00',
    $time_step='+1 hour',
    $total_steps=24
  ) {
    $steps = [];

    $formatted_time = $start_time;
    $timestamp = strtotime($formatted_time);

    for ($step = 0; $step < $total_steps; $step += 1) {
      $formatted_time = $this->formatTime($timestamp, self::DEFAULT_TIME_FORMAT);
      $label_time = $this->formatTime($timestamp);
      $steps[$formatted_time] = $label_time;

      $timestamp = strtotime($time_step, $timestamp);
    }

    return $steps;
  }
}
