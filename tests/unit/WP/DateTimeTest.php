<?php
use Helper\WordPress as WordPressHelper;
use MailPoet\WP\DateTime;

class DateTimeTest extends MailPoetTest {
  function _before() {
    $this->date_time = new DateTime();
  }

  function testGetTimeFormat() {
    WordPressHelper::interceptFunction('get_option', function($key) {
      return 'H:i';
    });
    expect($this->date_time->getTimeFormat())->equals('H:i');

    WordPressHelper::interceptFunction('get_option', function($key) {
      return '';
    });
    expect($this->date_time->getTimeFormat())->equals('H:i:s');
  }

  function testGetDateFormat() {
    WordPressHelper::interceptFunction('get_option', function($key) {
      return 'm-d';
    });
    expect($this->date_time->getDateFormat())->equals('m-d');

    WordPressHelper::interceptFunction('get_option', function($key) {
      return '';
    });
    expect($this->date_time->getDateFormat())->equals('Y-m-d');
  }

  function testGetCurrentDate() {
    expect($this->date_time->getCurrentDate("Y-m"))->equals(date("Y-m"));
  }

  function testGetCurrentTime() {
    expect($this->date_time->getCurrentTime("i:s"))->regExp('/\d\d:\d\d/');
  }

  function testFormatTime() {
    $timestamp = 1234567;
    $format = "H:i:s";
    expect($this->date_time->formatTime($timestamp, $format))->equals(date($format, $timestamp));
  }

  function testTimeInterval() {
    $one_hour_interval = array_keys($this->date_time->getTimeInterval(
      '00:00:00',
      '+1 hour',
      $total_steps=5
    ));
    $one_hour_expected = array(
      '00:00:00', '01:00:00', '02:00:00', '03:00:00', '04:00:00');
    expect($one_hour_interval)->equals($one_hour_expected);

    $quarter_hour_interval = array_keys($this->date_time->getTimeInterval(
      '00:00:00',
      '+15 minute',
      $total_steps=5
    ));
    $quarter_hour_expected = array(
      '00:00:00', '00:15:00', '00:30:00', '00:45:00', '01:00:00',
    );
    expect($quarter_hour_interval)->equals($quarter_hour_expected);

    $offset_start_time_interval = array_keys($this->date_time->getTimeInterval(
      '03:00:00',
      '+1 hour',
      $total_steps=5
    ));
    $offset_start_time_expected = array(
      '03:00:00', '04:00:00', '05:00:00', '06:00:00', '07:00:00',
    );
    expect($offset_start_time_interval)->equals($offset_start_time_expected);
  }

  function _afterStep() {
    WordPressHelper::releaseAllFunctions();
  }
}
