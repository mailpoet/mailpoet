<?php
use MailPoet\Models\StatisticsForms;

class StatisticsFormsTest extends MailPoetTest {

  function _before() {
    $this->yesterday = StatisticsForms::create();
    $this->yesterday->hydrate(array(
      'form_id' => 1,
      'count' => 10,
      'date' => date('Y-m-d', strtotime('yesterday'))
    ));
    $this->yesterday = $this->yesterday->save();
  }

  function testItCanBeCreated() {
    expect($this->yesterday->id() > 0)->true();
    expect($this->yesterday->getErrors())->false();
  }

  function testItCanRecordNewStats() {
    $today = StatisticsForms::record($form_id = 1);
    expect($today->count)->equals(1);
    expect($today->date)->equals(date('Y-m-d'));
    expect($today->form_id)->equals(1);
  }

  function testItCanAggregateStats() {
    $today = StatisticsForms::record($form_id = 2);
    expect($today->count)->equals(1);
    expect($today->date)->equals(date('Y-m-d'));
    expect($today->form_id)->equals(2);

    $today = StatisticsForms::record($form_id = 2);
    expect($today->count)->equals(2);
    expect($today->date)->equals(date('Y-m-d'));
    expect($today->form_id)->equals(2);
  }

  function _after() {
    StatisticsForms::deleteMany();
  }
}