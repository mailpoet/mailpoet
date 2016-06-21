<?php

use Codeception\Util\Stub;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\StatisticsUnsubscribes;
use MailPoet\Models\Subscriber;
use MailPoet\Statistics\Track\Opens;
use MailPoet\Statistics\Track\Unsubscribes;

class UnsubscribesTest extends MailPoetTest {
  function _before() {
    // create newsletter
    $newsletter = Newsletter::create();
    $newsletter->type = 'type';
    $this->newsletter = $newsletter->save();
    // create subscriber
    $subscriber = Subscriber::create();
    $subscriber->email = 'test@example.com';
    $subscriber->first_name = 'First';
    $subscriber->last_name = 'Last';
    $this->subscriber = $subscriber->save();
    // create queue
    $queue = SendingQueue::create();
    $queue->newsletter_id = $newsletter->id;
    $this->queue = $queue->save();
    // instantiate class
    $this->unsubscribes = new Unsubscribes();
  }

  function testItCanUniqueTrack() {
    $unsubscribe_events = StatisticsUnsubscribes::findArray();
    expect(count($unsubscribe_events))->equals(0);
    // only 1 unique unsubscribe event should be recorded
    $unsubscribes = $this->unsubscribes->track(
      $this->newsletter->id,
      $this->subscriber->id,
      $this->queue->id
    );
    $unsubscribes = $this->unsubscribes->track(
      $this->newsletter->id,
      $this->subscriber->id,
      $this->queue->id
    );
    $unsubscribe_events = StatisticsUnsubscribes::findArray();
    expect(count($unsubscribe_events))->equals(1);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsUnsubscribes::$_table);
  }
}
