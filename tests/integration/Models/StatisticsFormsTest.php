<?php
namespace MailPoet\Test\Models;

use MailPoet\Models\StatisticsForms;

class StatisticsFormsTest extends \MailPoetTest {

  function _before() {
  }

  function testItCanRecordStats() {
    $record = StatisticsForms::record($form_id = 1, $subscriber_id = 2);
    expect($record->form_id)->equals(1);
    expect($record->subscriber_id)->equals(2);
  }

  function testItDoesNotOverrideStats() {
    $record = StatisticsForms::record($form_id = 1, $subscriber_id = 2);
    expect($record->form_id)->equals(1);
    expect($record->subscriber_id)->equals(2);

    expect(StatisticsForms::count())->equals(1);
  }

  function testItCanRecordMultipleStats() {
    $record = StatisticsForms::record($form_id = 1, $subscriber_id = 2);
    $record2 = StatisticsForms::record($form_id = 2, $subscriber_id = 2);
    $record3 = StatisticsForms::record($form_id = 1, $subscriber_id = 1);

    expect(StatisticsForms::count())->equals(3);
  }

  function testItCannotRecordStatsWithoutFormOrSubscriber() {
    $record = StatisticsForms::record($form_id = null, $subscriber_id = 1);
    expect($record)->false();

    $record = StatisticsForms::record($form_id = 1, $subscriber_id = null);
    expect($record)->false();
  }

  function testItCanReturnTheTotalSignupsOfAForm() {
    // simulate 2 signups for form #1
    StatisticsForms::record($form_id = 1, $subscriber_id = 2);
    StatisticsForms::record($form_id = 1, $subscriber_id = 1);
    // simulate 1 signup for form #2
    StatisticsForms::record($form_id = 2, $subscriber_id = 2);

    $form_1_signups = StatisticsForms::getTotalSignups($form_id = 1);
    expect($form_1_signups)->equals(2);

    $form_2_signups = StatisticsForms::getTotalSignups($form_id = 2);
    expect($form_2_signups)->equals(1);
  }

  function _after() {
    StatisticsForms::deleteMany();
  }
}