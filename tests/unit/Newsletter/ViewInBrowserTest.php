<?php

use Codeception\Util\Stub;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\ViewInBrowser;

class ViewInBrowserTest extends MailPoetTest {
  function __construct() {
    $this->newsletter = array(
      'body' => json_decode(
        '{
          "content": {
            "type": "container",
            "orientation": "vertical",
            "styles": {
              "block": {
                "backgroundColor": "transparent"
              }
            },
            "blocks": [
              {
                "type": "container",
                "orientation": "horizontal",
                "styles": {
                  "block": {
                    "backgroundColor": "transparent"
                  }
                },
                "blocks": [
                  {
                    "type": "container",
                    "orientation": "vertical",
                    "styles": {
                      "block": {
                        "backgroundColor": "transparent"
                      }
                    },
                    "blocks": [
                      {
                        "type": "text",
                        "text": "<p>Rendered newsletter. Hello,&nbsp;[subscriber:firstname | default:reader] & [link:newsletter_view_in_browser_url]</p>"
                      }
                    ]
                  }
                ]
              }
            ]
          }
        }', true),
      'id' => 1,
      'subject' => 'Some subject',
      'preheader' => 'Some preheader',
      'type' => 'standard',
      'status' => 'active'
    );
    $this->queue_rendered_newsletter_without_tracking = json_encode(
      array(
        'html' => 'Newsletter from queue. Hello, [subscriber:firstname] & 
        [link:newsletter_view_in_browser_url]'
      )
    );
    $this->queue_rendered_newsletter_with_tracking = json_encode(
      array(
        'html' => 'Newsletter from queue. Hello, [subscriber:firstname] & 
        [mailpoet_click_data]-90e56'
      )
    );
    // instantiate class
    $this->view_in_browser = new ViewInBrowser();
  }

  function _before() {
    // create newsletter
    $newsletter = Newsletter::create();
    $newsletter->hydrate($this->newsletter);
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
    $queue->newsletter_rendered_body = $this->queue_rendered_newsletter_without_tracking;
    $queue->subscribers = array('processed' => array($subscriber->id));
    $this->queue = $queue->save();
    // build browser preview data
    $this->browser_preview_data = (object)array(
      'queue' => $this->queue,
      'subscriber' => $this->subscriber,
      'newsletter' => $this->newsletter,
      'preview' => false
    );
  }

  function testItAbortsWhenBrowserPreviewDataIsEmpty() {
    $view_in_browser = Stub::make($this->view_in_browser, array(
      'abort' => Stub::exactly(1, function() { })
    ), $this);
    $view_in_browser->view(false);
  }

  function testItRendersNewsletter() {
    $rendered_body = ViewInBrowser::renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $queue = false,
      $preview = true
    );
    expect($rendered_body)->regExp('/Rendered newsletter/');
  }

  function testItReusesRenderedNewsletterBodyWhenQueueExists() {
    $rendered_body = ViewInBrowser::renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = true
    );
    expect($rendered_body)->regExp('/Newsletter from queue/');
  }

  function testItConvertsShortcodes() {
    Setting::setValue('tracking.enabled', false);
    $rendered_body = ViewInBrowser::renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = true
    );
    expect($rendered_body)->regExp('/Hello, First/');
    expect($rendered_body)->regExp('/mailpoet_api&endpoint=view_in_browser/');
  }

  function testItProcessesLinksWhenTrackingIsEnabled() {
    Setting::setValue('tracking.enabled', true);
    $queue = $this->queue;
    $queue->newsletter_rendered_body = $this->queue_rendered_newsletter_with_tracking;
    $rendered_body = ViewInBrowser::renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $queue,
      $preview = true
    );
    expect($rendered_body)->regExp('/mailpoet_api&endpoint=track/');
  }

  function testItReturnsNewsletterPreview() {
    $view_in_browser = Stub::make($this->view_in_browser, array(
      'displayNewsletter' => Stub::exactly(1, function() { })
    ), $this);
    $view_in_browser->view($this->browser_preview_data);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}