<?php

use MailPoet\Form\Block\Date;

class DateTest extends MailPoetTest {
  function testItCanConvertDateMonthYearFormatToDatetime() {
    $date = array(
      'MM/DD/YYYY' => '05/10/2016',
      'DD/MM/YYYY' => '10/05/2016',
      'YYYY/MM/DD' => '2016/05/10',
      'YYYY/DD/MM' => '2016/10/05'
    );
    foreach($date as $date_format => $date) {
      expect(Date::convertDateToDatetime($date, $date_format))
        ->equals('2016-05-10 00:00:00');
    }
  }

  function testItCanConvertMonthYearFormatToDatetime() {
    $date = array(
      'MM/YYYY' => '05/2016',
      'YYYY/MM' => '2016/05'
    );
    foreach($date as $date_format => $date) {
      expect(Date::convertDATEToDatetime($date, $date_format))
        ->equals('2016-05-01 00:00:00');
    }
  }

  function testItCanConvertMonthToDatetime() {
    $current_year = date('Y');
    expect(Date::convertDateToDatetime('05', 'MM'))
      ->equals(sprintf('%s-05-01 00:00:00', $current_year));
  }

  function testItCanConvertYearToDatetime() {
    expect(Date::convertDateToDatetime('2016', 'YYYY'))
      ->equals('2016-01-01 00:00:00');
  }

  function testItCanConvertDatetimeToDatetime() {
    expect(Date::convertDateToDatetime('2016-05-10 00:00:00', 'datetime'))
      ->equals('2016-05-10 00:00:00');
  }

  function testItCanClearDate() {
    expect(Date::convertDateToDatetime('0/10/5', 'YYYY/MM/DD'))
      ->equals(date('Y') . '-10-05 00:00:00');
    expect(Date::convertDateToDatetime('0/0/5', 'YYYY/MM/DD'))
      ->equals(date('Y') . '-' . date('m') . '-05 00:00:00');
    expect(Date::convertDateToDatetime('0/0/0', 'YYYY/MM/DD'))
      ->equals('');
    expect(Date::convertDateToDatetime('0', 'YYYY'))
      ->equals('');
    expect(Date::convertDateToDatetime('0', 'MM'))
      ->equals('');
  }
}
