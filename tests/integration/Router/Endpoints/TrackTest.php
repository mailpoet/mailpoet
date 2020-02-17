<?php

namespace MailPoet\Test\Router\Endpoints;

use Codeception\Stub;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Router\Endpoints\Track;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\Clicks;
use MailPoet\Statistics\Track\Opens;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\Cookies;
use MailPoetVendor\Idiorm\ORM;

class TrackTest extends \MailPoetTest {
  public $track;
  public $trackData;
  public $link;
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
    // create link
    $link = NewsletterLink::create();
    $link->hash = 'hash';
    $link->url = 'url';
    $link->newsletterId = $newsletter->id;
    $link->queueId = $queue->id;
    $this->link = $link->save();
    $linkTokens = new LinkTokens;
    // build track data
    $this->trackData = [
      'queue_id' => $queue->id,
      'subscriber_id' => $subscriber->id,
      'newsletter_id' => $newsletter->id,
      'subscriber_token' => $linkTokens->getToken($subscriber),
      'link_hash' => $link->hash,
      'preview' => false,
    ];
    // instantiate class
    $this->track = new Track(new Clicks(SettingsController::getInstance(), new Cookies()), new Opens(), new LinkTokens());
  }

  public function testItReturnsFalseWhenTrackDataIsMissing() {
    // queue ID is required
    $data = $this->trackData;
    unset($data['queue_id']);
    expect($this->track->_processTrackData($data))->false();
    // subscriber ID is required
    $data = $this->trackData;
    unset($data['subscriber_id']);
    expect($this->track->_processTrackData($data))->false();
    // subscriber token is required
    $data = $this->trackData;
    unset($data['subscriber_token']);
    expect($this->track->_processTrackData($data))->false();
  }

  public function testItFailsWhenSubscriberTokenDoesNotMatch() {
    $data = (object)array_merge(
      $this->trackData,
      [
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter,
      ]
    );
    $data->subscriber->email = 'random@email.com';
    $track = Stub::make(Track::class, [
      'linkTokens' => new LinkTokens,
      'terminate' => function($code) {
        expect($code)->equals(403);
      },
    ]);
    $track->_validateTrackData($data);
  }

  public function testItFailsWhenSubscriberIsNotOnProcessedList() {
    $data = (object)array_merge(
      $this->trackData,
      [
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter,
      ]
    );
    $data->subscriber->id = 99;
    expect($this->track->_validateTrackData($data))->false();
  }

  public function testItDoesNotRequireWpUsersToBeOnProcessedListWhenPreviewIsEnabled() {
    $data = (object)array_merge(
      $this->trackData,
      [
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter,
      ]
    );
    $data->subscriber->wp_user_id = 99;
    $data->preview = true;
    expect($this->track->_validateTrackData($data))->equals($data);
  }

  public function testItRequiresValidQueueToGetNewsletter() {
    $data = $this->trackData;
    $data['newsletter_id'] = false;
    $data['queue_id'] = 99;
    $processedData = $this->track->_processTrackData($data);
    expect($processedData)->false();
  }

  public function testItGetsNewsletterFromQueue() {
    $data = $this->trackData;
    $data['newsletter_id'] = false;
    $processedData = $this->track->_processTrackData($data);
    expect($processedData->newsletter->id)->equals($this->newsletter->id);
  }

  public function testItProcessesTrackData() {
    $processedData = $this->track->_processTrackData($this->trackData);
    expect($processedData->queue->id)->equals($this->queue->id);
    expect($processedData->subscriber->id)->equals($this->subscriber->id);
    expect($processedData->newsletter->id)->equals($this->newsletter->id);
    expect($processedData->link->id)->equals($this->link->id);
  }

  public function testItGetsProperHashWhenDuplicateHashesExist() {
    // create another newsletter and queue
    $newsletter = Newsletter::create();
    $newsletter->type = 'type';
    $newsletter = $newsletter->save();
    $queue = SendingTask::create();
    $queue->newsletterId = $newsletter->id;
    $queue->setSubscribers([$this->subscriber->id]);
    $queue->updateProcessedSubscribers([$this->subscriber->id]);
    $queue->save();
    $trackData = $this->trackData;
    $trackData['queue_id'] = $queue->id;
    $trackData['newsletter_id'] = $newsletter->id;
    // create another link with the same hash but different queue ID
    $link = NewsletterLink::create();
    $link->hash = $this->link->hash;
    $link->url = $this->link->url;
    $link->newsletterId = $trackData['newsletter_id'];
    $link->queueId = $trackData['queue_id'];
    $link = $link->save();
    // assert that 2 links with identical hash exist
    $newsletterLink = NewsletterLink::where('hash', $link->hash)->findMany();
    expect($newsletterLink)->count(2);

    // assert that the fetched link ID belong to the newly created link
    $processedData = $this->track->_processTrackData($trackData);
    expect($processedData->link->id)->equals($link->id);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
