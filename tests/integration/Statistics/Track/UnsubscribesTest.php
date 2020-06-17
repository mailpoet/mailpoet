<?php

namespace MailPoet\Test\Statistics\Track;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsUnsubscribes;
use MailPoet\Models\Subscriber;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoetVendor\Idiorm\ORM;

class UnsubscribesTest extends \MailPoetTest {
  /** @var Unsubscribes */
  private $unsubscribes;

  public $queue;
  public $subscriber;
  public $newsletter;

  public function _before() {
    parent::_before();
    // create newsletter
    $newsletter = Newsletter::create();
    $newsletter->type = 'type';
    $this->newsletter = $newsletter->save();
    // create subscriber
    $subscriber = Subscriber::create();
    $subscriber->email = 'test@example.com';
    $subscriber->firstName = 'First';
    $subscriber->lastName = 'Last';
    $this->subscriber = $subscriber->save();
    // create queue
    $queue = SendingTask::create();
    $queue->newsletterId = $newsletter->id;
    $queue->setSubscribers([$subscriber->id]);
    $queue->updateProcessedSubscribers([$subscriber->id]);
    $this->queue = $queue->save();
    // instantiate class
    $this->unsubscribes = ContainerWrapper::getInstance()->get(Unsubscribes::class);
  }

  public function testItTracksUnsubscribeEvent() {
    $this->unsubscribes->track(
      $this->subscriber->id,
      $this->queue->id,
      'source'
    );
    expect(count(StatisticsUnsubscribes::findMany()))->equals(1);
  }

  public function testItDoesNotTrackRepeatedUnsubscribeEvents() {
    for ($count = 0; $count <= 2; $count++) {
      $this->unsubscribes->track(
        $this->subscriber->id,
        $this->queue->id,
        'source'
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
