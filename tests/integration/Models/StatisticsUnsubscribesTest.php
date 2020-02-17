<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\StatisticsUnsubscribes;

class StatisticsUnsubscribesTest extends \MailPoetTest {
  public function testItCanGetExistingStatisticsRecord() {
    $unsubscribeStatistics = StatisticsUnsubscribes::create();
    $unsubscribeStatistics->newsletterId = 123;
    $unsubscribeStatistics->subscriberId = 456;
    $unsubscribeStatistics->queueId = 789;
    $unsubscribeStatistics->save();
    $unsubscribeStatistics = StatisticsUnsubscribes::getOrCreate(456, 123, 789);
    expect($unsubscribeStatistics->newsletterId)->equals(123);
    expect($unsubscribeStatistics->subscriberId)->equals(456);
    expect($unsubscribeStatistics->queueId)->equals(789);
  }

  public function testItCanCreateNewStatisticsRecord() {
    expect(count(StatisticsUnsubscribes::findMany()))->equals(0);
    $unsubscribeStatistics = StatisticsUnsubscribes::getOrCreate(456, 123, 789);
    expect($unsubscribeStatistics->newsletterId)->equals(123);
    expect($unsubscribeStatistics->subscriberId)->equals(456);
    expect($unsubscribeStatistics->queueId)->equals(789);
  }

  public function _after() {
    StatisticsUnsubscribes::deleteMany();
  }
}
