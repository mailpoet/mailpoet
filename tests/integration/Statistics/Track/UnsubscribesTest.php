<?php

namespace MailPoet\Test\Statistics\Track;

use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsUnsubscribes;
use MailPoet\Models\Subscriber;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoetVendor\Idiorm\ORM;

class UnsubscribesTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
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
    $queue = SendingTask::create();
    $queue->newsletter_id = $newsletter->id;
    $queue->setSubscribers([$subscriber->id]);
    $queue->updateProcessedSubscribers([$subscriber->id]);
    $this->queue = $queue->save();
    // instantiate class
    $this->unsubscribes = new Unsubscribes();
  }

  public function testItTracksUnsubscribeEvent() {
    $this->unsubscribes->track(
      $this->newsletter->id,
      $this->subscriber->id,
      $this->queue->id
    );
    expect(count(StatisticsUnsubscribes::findMany()))->equals(1);
  }

  public function testItDoesNotTrackRepeatedUnsubscribeEvents() {
    for ($count = 0; $count <= 2; $count++) {
      $this->unsubscribes->track(
        $this->newsletter->id,
        $this->subscriber->id,
        $this->queue->id
      );
    }
    expect(count(StatisticsUnsubscribes::findMany()))->equals(1);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsUnsubscribes::$_table);
  }
}
