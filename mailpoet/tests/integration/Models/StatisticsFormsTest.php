<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\StatisticsForms;

class StatisticsFormsTest extends \MailPoetTest {
  public function testItCanRecordStats() {
    $record = StatisticsForms::record($formId = 1, $subscriberId = 2);
    expect($record->formId)->equals(1);
    expect($record->subscriberId)->equals(2);
  }

  public function testItDoesNotOverrideStats() {
    $record = StatisticsForms::record($formId = 1, $subscriberId = 2);
    expect($record->formId)->equals(1);
    expect($record->subscriberId)->equals(2);

    expect(StatisticsForms::count())->equals(1);
  }

  public function testItCanRecordMultipleStats() {
    $record = StatisticsForms::record($formId = 1, $subscriberId = 2);
    $record2 = StatisticsForms::record($formId = 2, $subscriberId = 2);
    $record3 = StatisticsForms::record($formId = 1, $subscriberId = 1);

    expect(StatisticsForms::count())->equals(3);
  }

  public function testItCannotRecordStatsWithoutFormOrSubscriber() {
    $record = StatisticsForms::record($formId = null, $subscriberId = 1);
    expect($record)->false();

    $record = StatisticsForms::record($formId = 1, $subscriberId = null);
    expect($record)->false();
  }

  public function testItCanReturnTheTotalSignupsOfAForm() {
    // simulate 2 signups for form #1
    StatisticsForms::record($formId = 1, $subscriberId = 2);
    StatisticsForms::record($formId = 1, $subscriberId = 1);
    // simulate 1 signup for form #2
    StatisticsForms::record($formId = 2, $subscriberId = 2);

    $form1Signups = StatisticsForms::getTotalSignups($formId = 1);
    expect($form1Signups)->equals(2);

    $form2Signups = StatisticsForms::getTotalSignups($formId = 2);
    expect($form2Signups)->equals(1);
  }

  public function _after() {
    StatisticsForms::deleteMany();
  }
}
