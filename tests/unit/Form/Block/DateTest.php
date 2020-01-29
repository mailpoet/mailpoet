<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Date;

class DateTest extends \MailPoetUnitTest {

  /** @var Date */
  private $date;

  public function _before() {
    parent::_before();
    $this->date = new Date();
  }

  public function testItCanConvertDateMonthYearFormatToDatetime() {
    $date = [
      'MM/DD/YYYY' => '05/10/2016',
      'DD/MM/YYYY' => '10/05/2016',
      'YYYY/MM/DD' => '2016/05/10',
      'YYYY/DD/MM' => '2016/10/05',
    ];
    foreach ($date as $dateFormat => $date) {
      expect($this->date->convertDateToDatetime($date, $dateFormat))
        ->equals('2016-05-10 00:00:00');
    }
  }

  public function testItCanConvertMonthYearFormatToDatetime() {
    $date = [
      'MM/YYYY' => '05/2016',
      'YYYY/MM' => '2016/05',
    ];
    foreach ($date as $dateFormat => $date) {
      expect($this->date->convertDATEToDatetime($date, $dateFormat))
        ->equals('2016-05-01 00:00:00');
    }
  }

  public function testItCanConvertMonthToDatetime() {
    $currentYear = date('Y');
    expect($this->date->convertDateToDatetime('05', 'MM'))
      ->equals(sprintf('%s-05-01 00:00:00', $currentYear));
  }

  public function testItCanConvertYearToDatetime() {
    expect($this->date->convertDateToDatetime('2016', 'YYYY'))
      ->equals('2016-01-01 00:00:00');
  }

  public function testItCanConvertDatetimeToDatetime() {
    expect($this->date->convertDateToDatetime('2016-05-10 00:00:00', 'datetime'))
      ->equals('2016-05-10 00:00:00');
  }

  public function testItCanClearDate() {
    expect($this->date->convertDateToDatetime('0/10/5', 'YYYY/MM/DD'))
      ->equals(date('Y') . '-10-05 00:00:00');
    expect($this->date->convertDateToDatetime('0/0/5', 'YYYY/MM/DD'))
      ->equals(date('Y') . '-' . date('m') . '-05 00:00:00');
    expect($this->date->convertDateToDatetime('0/0/0', 'YYYY/MM/DD'))
      ->equals('');
    expect($this->date->convertDateToDatetime('0', 'YYYY'))
      ->equals('');
    expect($this->date->convertDateToDatetime('0', 'MM'))
      ->equals('');
  }
}
