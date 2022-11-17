<?php declare(strict_types = 1);

namespace MailPoet\Test\WP;

use Codeception\Stub;
use MailPoet\WP\DateTime as WPDateTime;
use MailPoet\WP\Functions as WPFunctions;

class DateTimeTest extends \MailPoetUnitTest {
  public function testGetTimeFormat() {
    $dateTime = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return 'H:i';
      },
    ]));
    expect($dateTime->getTimeFormat())->equals('H:i');

    $dateTime = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return '';
      },
    ]));
    expect($dateTime->getTimeFormat())->equals('H:i:s');
  }

  public function testGetDateFormat() {
    $dateTime = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return 'm-d';
      },
    ]));
    expect($dateTime->getDateFormat())->equals('m-d');

    $dateTime = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return '';
      },
    ]));
    expect($dateTime->getDateFormat())->equals('Y-m-d');
  }

  public function testGetCurrentDate() {
    $dateTime = new WPDateTime(Stub::make(new WPFunctions(), [
      'currentTime' => function($format) {
        return date($format);
      },
    ]));
    expect($dateTime->getCurrentDate("Y-m"))->equals(date("Y-m"));
  }

  public function testGetCurrentTime() {
    $dateTime = new WPDateTime(Stub::make(new WPFunctions(), [
      'currentTime' => function($format) {
        return date($format);
      },
    ]));
    expect($dateTime->getCurrentTime("i:s"))->regExp('/\d\d:\d\d/');
  }

  public function testFormatTime() {
    $dateTime = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return 'H:i';
      },
    ]));
    $timestamp = 1234567;
    $format = "H:i:s";
    expect($dateTime->formatTime($timestamp))->equals(date($dateTime->getTimeFormat(), $timestamp));
    expect($dateTime->formatTime($timestamp, $format))->equals(date($format, $timestamp));
  }

  public function testFormatDate() {
    $dateTime = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return 'm-d';
      },
    ]));
    $timestamp = 1234567;
    $format = "Y-m-d";
    expect($dateTime->formatDate($timestamp))->equals(date($dateTime->getDateFormat(), $timestamp));
    expect($dateTime->formatDate($timestamp, $format))->equals(date($format, $timestamp));
  }

  public function testTimeInterval() {
    $dateTime = new WPDateTime(Stub::make(new WPFunctions(), [
      'getOption' => function($key) {
        return 'H:i';
      },
    ]));
    $oneHourInterval = array_keys($dateTime->getTimeInterval(
      '00:00:00',
      '+1 hour',
      $totalSteps = 5
    ));
    $oneHourExpected = [
      '00:00:00', '01:00:00', '02:00:00', '03:00:00', '04:00:00'];
    expect($oneHourInterval)->equals($oneHourExpected);

    $quarterHourInterval = array_keys($dateTime->getTimeInterval(
      '00:00:00',
      '+15 minute',
      $totalSteps = 5
    ));
    $quarterHourExpected = [
      '00:00:00', '00:15:00', '00:30:00', '00:45:00', '01:00:00',
    ];
    expect($quarterHourInterval)->equals($quarterHourExpected);

    $offsetStartTimeInterval = array_keys($dateTime->getTimeInterval(
      '03:00:00',
      '+1 hour',
      $totalSteps = 5
    ));
    $offsetStartTimeExpected = [
      '03:00:00', '04:00:00', '05:00:00', '06:00:00', '07:00:00',
    ];
    expect($offsetStartTimeInterval)->equals($offsetStartTimeExpected);
  }
}
