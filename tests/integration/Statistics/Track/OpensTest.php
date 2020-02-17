<?php

namespace MailPoet\Test\Statistics\Track;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Statistics\Track\Opens;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoetVendor\Idiorm\ORM;

class OpensTest extends \MailPoetTest {
  public $opens;
  public $trackData;
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
    $linkTokens = new LinkTokens;
    // build track data
    $this->trackData = (object)[
      'queue' => $queue,
      'subscriber' => $subscriber,
      'newsletter' => $newsletter,
      'subscriber_token' => $linkTokens->getToken($subscriber),
      'preview' => false,
    ];
    // instantiate class
    $this->opens = new Opens();
  }

  public function testItReturnsImageWhenTrackDataIsEmpty() {
    $opens = Stub::make($this->opens, [
      'returnResponse' => Expected::exactly(1),
    ], $this);
    $opens->track(false);
    expect(StatisticsOpens::findMany())->isEmpty();
  }

  public function testItDoesNotTrackOpenEventFromWpUserWhenPreviewIsEnabled() {
    $data = $this->trackData;
    $data->subscriber->wp_user_id = 99;
    $data->preview = true;
    $opens = Stub::make($this->opens, [
      'returnResponse' => null,
    ], $this);
    $opens->track($data);
    expect(StatisticsOpens::findMany())->isEmpty();
  }

  public function testItReturnsNothingWhenImageDisplayIsDisabled() {
    expect($this->opens->track($this->trackData, $displayImage = false))->isEmpty();
  }

  public function testItTracksOpenEvent() {
    $opens = Stub::make($this->opens, [
      'returnResponse' => null,
    ], $this);
    $opens->track($this->trackData);
    expect(StatisticsOpens::findMany())->notEmpty();
  }

  public function testItDoesNotTrackRepeatedOpenEvents() {
    $opens = Stub::make($this->opens, [
      'returnResponse' => null,
    ], $this);
    for ($count = 0; $count <= 2; $count++) {
      $opens->track($this->trackData);
    }
    expect(count(StatisticsOpens::findMany()))->equals(1);
  }

  public function testItReturnsImageAfterTracking() {
    $opens = Stub::make($this->opens, [
      'returnResponse' => Expected::exactly(1),
    ], $this);
    $opens->track($this->trackData);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsOpens::$_table);
  }
}
