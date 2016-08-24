<?php

use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Router\Endpoints\ViewInBrowser;

class ViewInBrowserRouterTest extends MailPoetTest {
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
    // build browser preview data
    $this->browser_preview_data = array(
      'queue_id' => $queue->id,
      'subscriber_id' => $subscriber->id,
      'newsletter_id' => $newsletter->id,
      'subscriber_token' => Subscriber::generateToken($subscriber->email),
      'preview' => false
    );
  }

  function testItReturnsFalseWhenTrackDataIsMissing() {
    // queue ID is required
    $data = $this->browser_preview_data;
    unset($data['newsletter_id']);
    expect(ViewInBrowser::_processBrowserPreviewData($data))->false();
    // subscriber ID is required
    $data = $this->browser_preview_data;
    unset($data['subscriber_id']);
    expect(ViewInBrowser::_processBrowserPreviewData($data))->false();
    // subscriber token is required
    $data = $this->browser_preview_data;
    unset($data['subscriber_token']);
    expect(ViewInBrowser::_processBrowserPreviewData($data))->false();
  }

  function testItFailsWhenSubscriberTokenDoesNotMatch() {
    $data = (object)array_merge(
      $this->browser_preview_data,
      array(
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter
      )
    );
    $data->subscriber->email = 'random@email.com';
    expect(ViewInBrowser::_validateBrowserPreviewData($data))->false();
  }

  function testItFailsWhenSubscriberIsNotOnProcessedList() {
    $data = (object)array_merge(
      $this->browser_preview_data,
      array(
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter
      )
    );
    $data->subscriber->id = 99;
    expect(ViewInBrowser::_validateBrowserPreviewData($data))->false();
  }

  function testItDoesNotRequireWpUsersToBeOnProcessedListWhenPreviewIsEnabled() {
    $data = (object)array_merge(
      $this->browser_preview_data,
      array(
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter
      )
    );
    $data->subscriber->wp_user_id = 99;
    $data->preview = true;
    expect(ViewInBrowser::_validateBrowserPreviewData($data))->equals($data);
  }

  function testItCanProcessBrowserPreviewData() {
    $processed_data = ViewInBrowser::_processBrowserPreviewData($this->browser_preview_data);
    expect($processed_data->queue->id)->equals($this->queue->id);
    expect($processed_data->subscriber->id)->equals($this->subscriber->id);
    expect($processed_data->newsletter->id)->equals($this->newsletter->id);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}