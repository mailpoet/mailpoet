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
use MailPoet\Tasks\Sending as SendingTask;

class OpensTest extends \MailPoetTest {
  function _before() {
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
    $queue->setSubscribers(array($subscriber->id));
    $queue->updateProcessedSubscribers(array($subscriber->id));
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
      'returnResponse' => Expected::exactly(1)
    ), $this);
    $opens->track(false);
    expect(StatisticsOpens::findMany())->isEmpty();
  }

  function testItDoesNotTrackOpenEventFromWpUserWhenPreviewIsEnabled() {
    $data = $this->track_data;
    $data->subscriber->wp_user_id = 99;
    $data->preview = true;
    $opens = Stub::make($this->opens, array(
      'returnResponse' => null
    ), $this);
    $opens->track($data);
    expect(StatisticsOpens::findMany())->isEmpty();
  }

  function testItReturnsNothingWhenImageDisplayIsDisabled() {
    expect($this->opens->track($this->track_data, $display_image = false))->isEmpty();
  }

  function testItTracksOpenEvent() {
    $opens = Stub::make($this->opens, array(
      'returnResponse' => null
    ), $this);
    $opens->track($this->track_data);
    expect(StatisticsOpens::findMany())->notEmpty();
  }

  function testItDoesNotTrackRepeatedOpenEvents() {
    $opens = Stub::make($this->opens, array(
      'returnResponse' => null
    ), $this);
    for ($count = 0; $count <= 2; $count++) {
      $opens->track($this->track_data);
    }
    expect(count(StatisticsOpens::findMany()))->equals(1);
  }

  function testItReturnsImageAfterTracking() {
    $opens = Stub::make($this->opens, array(
      'returnResponse' => Expected::exactly(1)
    ), $this);
    $opens->track($this->track_data);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . StatisticsOpens::$_table);
  }
}
