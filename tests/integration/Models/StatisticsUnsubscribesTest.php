<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\StatisticsUnsubscribes;
use MailPoetVendor\Idiorm\ORM;

class StatisticsUnsubscribesTest extends \MailPoetTest {
  public function testItCanGetExistingStatisticsRecord() {
    $unsubscribe_statistics = StatisticsUnsubscribes::create();
    $unsubscribe_statistics->newsletter_id = 123;
    $unsubscribe_statistics->subscriber_id = 456;
    $unsubscribe_statistics->queue_id = 789;
    $unsubscribe_statistics->save();
    $unsubscribe_statistics = StatisticsUnsubscribes::getOrCreate(456, 123, 789);
    expect($unsubscribe_statistics->newsletter_id)->equals(123);
    expect($unsubscribe_statistics->subscriber_id)->equals(456);
    expect($unsubscribe_statistics->queue_id)->equals(789);
  }

  public function testItCanCreateNewStatisticsRecord() {
    expect(count(StatisticsUnsubscribes::findMany()))->equals(0);
    $unsubscribe_statistics = StatisticsUnsubscribes::getOrCreate(456, 123, 789);
    expect($unsubscribe_statistics->newsletter_id)->equals(123);
    expect($unsubscribe_statistics->subscriber_id)->equals(456);
    expect($unsubscribe_statistics->queue_id)->equals(789);
  }

  public function _after() {
    ORM::for_table(StatisticsUnsubscribes::$_table)
      ->deleteMany();
  }
}
