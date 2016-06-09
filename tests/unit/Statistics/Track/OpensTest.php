<?php

use Codeception\Util\Stub;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Statistics\Track\Opens;

class OpensTest extends MailPoetTest {
  function _before() {
    // create newsletter
    $newsletter = Newsletter::create();
    $newsletter->type = 'type';
    $this->newsletter = $newsletter->save();
    // create subscriber
    $subscriber = Subscriber::create();
    $subscriber->email = 'test@example.com';
    $this->subscriber = $subscriber->save();
    // create queue
    $queue = SendingQueue::create();
    $queue->newsletter_id = $newsletter->id;
    $this->queue = $queue->save();
    // instantiate class
    $this->opens = new Opens($data = true, $return_image = false);
  }

  function testItCanConstruct() {
    $opens = new Opens($data = 'test', $return_image = true);
    expect($opens->data)->equals('test');
    expect($opens->return_image)->true();
  }

  function testItCanGetNewsletter() {
    $newsletter = $this->opens->getNewsletter($this->newsletter->id);
    expect(is_array($newsletter))->true();
    expect($newsletter['id'])->equals($this->newsletter->id);
  }

  function testItCanGetSubscriber() {
    $subscriber = $this->opens->getSubscriber($this->subscriber->id);
    expect(is_array($subscriber))->true();
    expect($subscriber['id'])->equals($this->subscriber->id);
  }

  function testItCanGetQueue() {
    $queue = $this->opens->getQueue($this->queue->id);
    expect(is_array($queue))->true();
    expect($queue['id'])->equals($this->queue->id);
  }

  function testItReturnsWhenItCantFindData() {
    // should return false when newsletter can't be found
    $data = array(
      'newsletter' => 999,
      'subscriber' => $this->subscriber->id,
      'queue' => $this->queue->id
    );
    expect($this->opens->track($data))->false();
    // should return false when subscriber can't be found
    $data = array(
      'newsletter' => $this->newsletter->id,
      'subscriber' => 999,
      'queue' => $this->queue->id
    );
    expect($this->opens->track($data))->false();
    // should return false when queue can't be found
    $data = array(
      'newsletter' => $this->newsletter->id,
      'subscriber' => $this->subscriber->id,
      'queue' => 999
    );
    expect($this->opens->track($data))->false();
  }

  function testItReturnsTrueOrImageUponCompletion() {
    $data = array(
      'newsletter' => $this->newsletter->id,
      'subscriber' => $this->subscriber->id,
      'queue' => $this->queue->id
    );
    $opens = Stub::make(new Opens(true), array(
      'returnImage' => Stub::exactly(1, function () { }),
      'data' => $data,
      'return_image' => true
    ), $this);
    // it should run returnImage() method when $return_image is set to true
    $opens->track();
    $opens = Stub::make(new Opens(true), array(
      'returnImage' => Stub::exactly(0, function () { }),
      'data' => $data,
      'return_image' => false
    ), $this);
    // it should return true when $return_image is set to false
    expect($opens->track())->true();
  }

  function testItTracksOnlyUniqueOpenEvent() {
    $data = array(
      'newsletter' => $this->newsletter->id,
      'subscriber' => $this->subscriber->id,
      'queue' => $this->queue->id
    );
    $open_events = StatisticsOpens::findArray();
    expect(count($open_events))->equals(0);
    // tracking twice the same event should only create 1 record
    $open = $this->opens->track($data);
    $open = $this->opens->track($data);
    $open_events = StatisticsOpens::findArray();
    expect(count($open_events))->equals(1);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsOpens::$_table);
  }
}
