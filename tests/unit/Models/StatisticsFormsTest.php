<?php
use MailPoet\Models\StatisticsForms;

class StatisticsFormsTest extends MailPoetTest {

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

  function _after() {
    StatisticsForms::deleteMany();
  }
}