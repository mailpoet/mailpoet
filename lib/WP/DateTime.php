<?php

namespace MailPoet\WP;

use MailPoet\WP\Functions as WPFunctions;

class DateTime {

  const DEFAULT_DATE_FORMAT = 'Y-m-d';
  const DEFAULT_TIME_FORMAT = 'H:i:s';
  const DEFAULT_DATE_TIME_FORMAT = 'Y-m-d H:i:s';

  /** @var WPFunctions */
  private $wp;

  public function __construct(WPFunctions $wp = null) {
    if ($wp === null) {
      $wp = new WPFunctions();
    }
    $this->wp = $wp;
  }

  public function getTimeFormat() {
    $timeFormat = $this->wp->getOption('time_format');
    if (empty($timeFormat)) $timeFormat = self::DEFAULT_TIME_FORMAT;
    return $timeFormat;
  }

  public function getDateFormat() {
    $dateFormat = $this->wp->getOption('date_format');
    if (empty($dateFormat)) $dateFormat = self::DEFAULT_DATE_FORMAT;
    return $dateFormat;
  }

  public function getCurrentTime($format=false) {
    if (empty($format)) $format = $this->getTimeFormat();
    return $this->wp->currentTime($format);
  }

  public function getCurrentDate($format=false) {
    if (empty($format)) $format = $this->getDateFormat();
    return $this->getCurrentTime($format);
  }

  public function formatTime($timestamp, $format=false) {
    if (empty($format)) $format = $this->getTimeFormat();

    return date($format, $timestamp);
  }

  public function formatDate($timestamp, $format=false) {
    if (empty($format)) $format = $this->getDateFormat();

    return date($format, $timestamp);
  }

  /**
   * Generates a list of time strings within an interval,
   * formatted and mapped from DEFAULT_TIME_FORMAT to WordPress time strings.
   */
  public function getTimeInterval(
    $startTime='00:00:00',
    $timeStep='+1 hour',
    $totalSteps=24
  ) {
    $steps = [];

    $formattedTime = $startTime;
    $timestamp = strtotime($formattedTime);

    for ($step = 0; $step < $totalSteps; $step += 1) {
      $formattedTime = $this->formatTime($timestamp, self::DEFAULT_TIME_FORMAT);
      $labelTime = $this->formatTime($timestamp);
      $steps[$formattedTime] = $labelTime;

      $timestamp = strtotime($timeStep, $timestamp);
    }

    return $steps;
  }
}
