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
    expect(Date::convertDateToDatetime('05', 'MM'))
      ->equals('2016-05-01 00:00:00');
  }

  function testItCanConvertYearToDatetime() {
    expect(Date::convertDateToDatetime('2016', 'YYYY'))
      ->equals('2016-01-01 00:00:00');
  }

  function testItCanConvertDatetimeToDatetime() {
    expect(Date::convertDateToDatetime('2016-05-10 00:00:00', 'datetime'))
      ->equals('2016-05-10 00:00:00');
  }

}