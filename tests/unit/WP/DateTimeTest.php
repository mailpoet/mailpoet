<?php

namespace MailPoet\Test\WP;

use Codeception\Stub;
use MailPoet\WP\DateTime as WPDateTime;
use MailPoet\WP\Functions as WPFunctions;

class DateTimeTest extends \MailPoetUnitTest {

  public function testGetTimeFormat() {
    $date_time = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return 'H:i';
      },
    ]));
    expect($date_time->getTimeFormat())->equals('H:i');

    $date_time = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return '';
      },
    ]));
    expect($date_time->getTimeFormat())->equals('H:i:s');
  }

  public function testGetDateFormat() {
    $date_time = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return 'm-d';
      },
    ]));
    expect($date_time->getDateFormat())->equals('m-d');

    $date_time = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return '';
      },
    ]));
    expect($date_time->getDateFormat())->equals('Y-m-d');
  }

  public function testGetCurrentDate() {
    $date_time = new WPDateTime(Stub::make(new WPFunctions(), [
      'currentTime' => function($format) {
        return date($format);
      },
    ]));
    expect($date_time->getCurrentDate("Y-m"))->equals(date("Y-m"));
  }

  public function testGetCurrentTime() {
    $date_time = new WPDateTime(Stub::make(new WPFunctions(), [
      'currentTime' => function($format) {
        return date($format);
      },
    ]));
    expect($date_time->getCurrentTime("i:s"))->regExp('/\d\d:\d\d/');
  }

  public function testFormatTime() {
    $date_time = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return 'H:i';
      },
    ]));
    $timestamp = 1234567;
    $format = "H:i:s";
    expect($date_time->formatTime($timestamp))->equals(date($date_time->getTimeFormat(), $timestamp));
    expect($date_time->formatTime($timestamp, $format))->equals(date($format, $timestamp));
  }

  public function testFormatDate() {
    $date_time = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return 'm-d';
      },
    ]));
    $timestamp = 1234567;
    $format = "Y-m-d";
    expect($date_time->formatDate($timestamp))->equals(date($date_time->getDateFormat(), $timestamp));
    expect($date_time->formatDate($timestamp, $format))->equals(date($format, $timestamp));
  }

  public function testTimeInterval() {
    $date_time = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return 'H:i';
      },
    ]));
    $one_hour_interval = array_keys($date_time->getTimeInterval(
      '00:00:00',
      '+1 hour',
      $total_steps = 5
    ));
    $one_hour_expected = [
      '00:00:00', '01:00:00', '02:00:00', '03:00:00', '04:00:00'];
    expect($one_hour_interval)->equals($one_hour_expected);

    $quarter_hour_interval = array_keys($date_time->getTimeInterval(
      '00:00:00',
      '+15 minute',
      $total_steps = 5
    ));
    $quarter_hour_expected = [
      '00:00:00', '00:15:00', '00:30:00', '00:45:00', '01:00:00',
    ];
    expect($quarter_hour_interval)->equals($quarter_hour_expected);

    $offset_start_time_interval = array_keys($date_time->getTimeInterval(
      '03:00:00',
      '+1 hour',
      $total_steps = 5
    ));
    $offset_start_time_expected = [
      '03:00:00', '04:00:00', '05:00:00', '06:00:00', '07:00:00',
    ];
    expect($offset_start_time_interval)->equals($offset_start_time_expected);
  }
}
