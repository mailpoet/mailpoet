<?php

use Codeception\Util\Stub;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
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
    $subscriber->first_name = 'First';
    $subscriber->last_name = 'Last';
    $this->subscriber = $subscriber->save();
    // create queue
    $queue = SendingQueue::create();
    $queue->newsletter_id = $newsletter->id;
    $queue->subscribers = array('processed' => array($subscriber->id));
    $this->queue = $queue->save();
    // build track data
    $this->track_data = (object)array(
      'queue' => $queue,
      'subscriber' => $subscriber,
      'newsletter' => $newsletter,
      'subscriber_token' => Subscriber::generateToken($subscriber->email),
      'preview' => false
    );
    // instantiate class
    $this->opens = new Opens();
  }

  function testItReturnsImageWhenTrackDataIsEmpty() {
    $opens = Stub::make($this->opens, array(
      'returnResponse' => Stub::exactly(1, function() { })
    ), $this);
    $opens->track(false);
    expect(StatisticsOpens::findMany())->isEmpty();
  }

  function testItDoesNotTrackOpenEventFromWpUserWhenPreviewIsEnabled() {
    $data = $this->track_data;
    $data->subscriber->wp_user_id = 99;
    $data->preview = true;
    $opens = Stub::make($this->opens, array(
      'returnResponse' => function() { }
    ), $this);
    $opens->track($data);
    expect(StatisticsOpens::findMany())->isEmpty();
  }

  function testItReturnsNothingWhenImageDisplayIsDisabled() {
    expect($this->opens->track($this->track_data, $display_image = false))->isEmpty();
  }

  function testItTracksOpenEvent() {
    $opens = Stub::make($this->opens, array(
      'returnResponse' => function() { }
    ), $this);
    $opens->track($this->track_data);
    expect(StatisticsOpens::findMany())->notEmpty();
  }

  function testItDoesNotTrackRepeatedOpenEvents() {
    $opens = Stub::make($this->opens, array(
      'returnResponse' => function() { }
    ), $this);
    for($count = 0; $count <= 2; $count++) {
      $opens->track($this->track_data);
    }
    expect(count(StatisticsOpens::findMany()))->equals(1);
  }

  function testItReturnsImageAfterTracking() {
    $opens = Stub::make($this->opens, array(
      'returnResponse' => Stub::exactly(1, function() { })
    ), $this);
    $opens->track($this->track_data);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsOpens::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
  }
}