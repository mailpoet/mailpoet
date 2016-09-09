<?php

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Router\Endpoints\Track;

class TrackTest extends MailPoetTest {
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
    // create link
    $link = NewsletterLink::create();
    $link->hash = 'hash';
    $link->url = 'url';
    $link->newsletter_id = $newsletter->id;
    $link->queue_id = $queue->id;
    $this->link = $link->save();
    // build track data
    $this->track_data = array(
      'queue_id' => $queue->id,
      'subscriber_id' => $subscriber->id,
      'newsletter_id' => $newsletter->id,
      'subscriber_token' => Subscriber::generateToken($subscriber->email),
      'link_hash' => $link->hash,
      'preview' => false
    );
    // instantiate class
    $this->track = new Track($this->track_data);
  }

  function testItReturnsFalseWhenTrackDataIsMissing() {
    // queue ID is required
    $data = $this->track_data;
    unset($data['queue_id']);
    expect($this->track->_processTrackData($data))->false();
    // subscriber ID is required
    $data = $this->track_data;
    unset($data['subscriber_id']);
    expect($this->track->_processTrackData($data))->false();
    // subscriber token is required
    $data = $this->track_data;
    unset($data['subscriber_token']);
    expect($this->track->_processTrackData($data))->false();
  }

  function testItFailsWhenSubscriberTokenDoesNotMatch() {
    $data = (object)array_merge(
      $this->track_data,
      array(
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter
      )
    );
    $data->subscriber->email = 'random@email.com';
    expect($this->track->_validateTrackData($data))->false();
  }

  function testItFailsWhenSubscriberIsNotOnProcessedList() {
    $data = (object)array_merge(
      $this->track_data,
      array(
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter
      )
    );
    $data->subscriber->id = 99;
    expect($this->track->_validateTrackData($data))->false();
  }

  function testItDoesNotRequireWpUsersToBeOnProcessedListWhenPreviewIsEnabled() {
    $data = (object)array_merge(
      $this->track_data,
      array(
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter
      )
    );
    $data->subscriber->wp_user_id = 99;
    $data->preview = true;
    expect($this->track->_validateTrackData($data))->equals($data);
  }

  function testItRequiresValidQueueToGetNewsletter() {
    $data = $this->track_data;
    $data['newsletter_id'] = false;
    $data['queue_id'] = 99;
    $processed_data = $this->track->_processTrackData($data);
    expect($processed_data)->false();
  }

  function testItGetsNewsletterFromQueue() {
    $data = $this->track_data;
    $data['newsletter_id'] = false;
    $processed_data = $this->track->_processTrackData($data);
    expect($processed_data->newsletter->id)->equals($this->newsletter->id);
  }

  function testItProcessesTrackData() {
    $processed_data = $this->track->_processTrackData($this->track_data);
    expect($processed_data->queue->id)->equals($this->queue->id);
    expect($processed_data->subscriber->id)->equals($this->subscriber->id);
    expect($processed_data->newsletter->id)->equals($this->newsletter->id);
    expect($processed_data->link->id)->equals($this->link->id);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}