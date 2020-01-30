<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Util\DateConverter;

class DateConverterTest extends \MailPoetUnitTest {

  /** @var DateConverter */
  private $dateConverter;

  public function _before() {
    parent::_before();
    $this->dateConverter = new DateConverter();
  }

  public function testItCanConvertDateMonthYearFormatToDatetime() {
    $date = [
      'MM/DD/YYYY' => '05/10/2016',
      'DD/MM/YYYY' => '10/05/2016',
      'YYYY/MM/DD' => '2016/05/10',
      'YYYY/DD/MM' => '2016/10/05',
    ];
    foreach ($date as $dateFormat => $date) {
      expect($this->dateConverter->convertDateToDatetime($date, $dateFormat))
        ->equals('2016-05-10 00:00:00');
    }
  }

  public function testItCanConvertMonthYearFormatToDatetime() {
    $date = [
      'MM/YYYY' => '05/2016',
      'YYYY/MM' => '2016/05',
    ];
    foreach ($date as $dateFormat => $date) {
      expect($this->dateConverter->convertDateToDatetime($date, $dateFormat))
        ->equals('2016-05-01 00:00:00');
    }
  }

  public function testItCanConvertMonthToDatetime() {
    $currentYear = date('Y');
    expect($this->dateConverter->convertDateToDatetime('05', 'MM'))
      ->equals(sprintf('%s-05-01 00:00:00', $currentYear));
  }

  public function testItCanConvertYearToDatetime() {
    expect($this->dateConverter->convertDateToDatetime('2016', 'YYYY'))
      ->equals('2016-01-01 00:00:00');
  }

  public function testItCanConvertDatetimeToDatetime() {
    expect($this->dateConverter->convertDateToDatetime('2016-05-10 00:00:00', 'datetime'))
      ->equals('2016-05-10 00:00:00');
  }

  public function testItCanClearDate() {
    expect($this->dateConverter->convertDateToDatetime('0/10/5', 'YYYY/MM/DD'))
      ->equals(date('Y') . '-10-05 00:00:00');
    expect($this->dateConverter->convertDateToDatetime('0/0/5', 'YYYY/MM/DD'))
      ->equals(date('Y') . '-' . date('m') . '-05 00:00:00');
    expect($this->dateConverter->convertDateToDatetime('0/0/0', 'YYYY/MM/DD'))
      ->equals('');
    expect($this->dateConverter->convertDateToDatetime('0', 'YYYY'))
      ->equals('');
    expect($this->dateConverter->convertDateToDatetime('0', 'MM'))
      ->equals('');
  }
}
