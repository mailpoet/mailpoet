<?php

use Codeception\Util\Stub;
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
    // instantiate class
    $this->view_in_browser = new ViewInBrowser($this->browser_preview_data);
  }

  function testItAbortsWhenBrowserPreviewDataIsMissing() {
    $view_in_browser = Stub::make($this->view_in_browser, array(
      '_abort' => Stub::exactly(3, function() { })
    ), $this);
    // newsletter ID is required
    $data = $this->browser_preview_data;
    unset($data['newsletter_id']);
    $view_in_browser->_processBrowserPreviewData($data);
    // subscriber ID is required
    $data = $this->browser_preview_data;
    unset($data['subscriber_id']);
    $view_in_browser->_processBrowserPreviewData($data);
    // subscriber token is required
    $data = $this->browser_preview_data;
    unset($data['subscriber_token']);
    $view_in_browser->_processBrowserPreviewData($data);
  }

  function testItAbortsWhenBrowserPreviewDataIsInvalid() {
    $view_in_browser = Stub::make($this->view_in_browser, array(
      '_abort' => Stub::exactly(3, function() { })
    ), $this);
    // newsletter ID is invalid
    $data = $this->browser_preview_data;
    $data['newsletter_id'] = 99;
    $view_in_browser->_processBrowserPreviewData($data);
    // subscriber ID is invalid
    $data = $this->browser_preview_data;
    $data['subscriber_id'] = 99;
    $view_in_browser->_processBrowserPreviewData($data);
    // subscriber token is invalid
    $data = $this->browser_preview_data;
    $data['subscriber_token'] = 'invalid';
    $view_in_browser->_processBrowserPreviewData($data);
  }

  function testItFailsValidationWhenSubscriberTokenDoesNotMatch() {
    $data = (object)array_merge(
      $this->browser_preview_data,
      array(
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter
      )
    );
    $data->subscriber->email = 'random@email.com';
    expect($this->view_in_browser->_validateBrowserPreviewData($data))->false();
  }

  function testItFailsValidationWhenSubscriberIsNotOnProcessedList() {
    $data = (object)array_merge(
      $this->browser_preview_data,
      array(
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter
      )
    );
    $data->subscriber->id = 99;
    expect($this->view_in_browser->_validateBrowserPreviewData($data))->false();
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
    expect($this->view_in_browser->_validateBrowserPreviewData($data))->equals($data);
  }

  function testItProcessesBrowserPreviewData() {
    $processed_data = $this->view_in_browser->_processBrowserPreviewData($this->browser_preview_data);
    expect($processed_data->queue->id)->equals($this->queue->id);
    expect($processed_data->subscriber->id)->equals($this->subscriber->id);
    expect($processed_data->newsletter->id)->equals($this->newsletter->id);
  }

  function testItReturnsViewActionResult() {
    $view_in_browser = Stub::make($this->view_in_browser, array(
      '_displayNewsletter' => Stub::exactly(1, function() { })
    ), $this);
    $view_in_browser->data = $view_in_browser->_processBrowserPreviewData($this->browser_preview_data);
    $view_in_browser->view();
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}