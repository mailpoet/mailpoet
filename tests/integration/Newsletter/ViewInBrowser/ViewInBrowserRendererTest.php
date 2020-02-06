<?php

namespace MailPoet\Newsletter\ViewInBrowser;

use Codeception\Stub\Expected;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\ViewInBrowserRenderer;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Emoji;
use MailPoetVendor\Idiorm\ORM;

class ViewInBrowserRendererTest extends \MailPoetTest {
  public $queueRenderedNewsletterWithTracking;
  public $queueRenderedNewsletterWithoutTracking;
  public $newsletterLink2;
  public $newsletterLink1;
  public $queue;
  public $subscriber;
  public $viewInBrowser;
  public $emoji;
  public $newsletter;
  public function _before() {
    parent::_before();
    $this->newsletter =
      [
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
                        "text": "<p>Rendered newsletter. Hello, [subscriber:firstname | default:reader]. <a href=\"[link:newsletter_view_in_browser_url]\">Unsubscribe</a> or visit <a href=\"http://google.com\">Google</a></p>"
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
        'status' => 'active',
      ];
    $this->queueRenderedNewsletterWithoutTracking = [
      'html' => '<p>Newsletter from queue. Hello, [subscriber:firstname | default:reader]. <a href="[link:newsletter_view_in_browser_url]">Unsubscribe</a> or visit <a href="http://google.com">Google</a></p>',
      'text' => 'test',
    ];
    $this->queueRenderedNewsletterWithTracking = [
      'html' => '<p>Newsletter from queue. Hello, [subscriber:firstname | default:reader]. <a href="' . Links::DATA_TAG_CLICK . '-90e56">Unsubscribe</a> or visit <a href="' . Links::DATA_TAG_CLICK . '-i1893">Google</a><img alt="" class="" src="' . Links::DATA_TAG_OPEN . '"></p>',
      'text' => 'test',
    ];
    $this->emoji = new Emoji();
    $this->viewInBrowser = new ViewInBrowserRenderer($this->emoji, false);
    // create newsletter
    $newsletter = Newsletter::create();
    $newsletter->hydrate($this->newsletter);
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
    $queue->newsletterRenderedBody = $this->queueRenderedNewsletterWithoutTracking;
    $queue->setSubscribers([$subscriber->id]);
    $this->queue = $queue->save();
    // create newsletter link associations
    $newsletterLink1 = NewsletterLink::create();
    $newsletterLink1->hash = '90e56';
    $newsletterLink1->url = '[link:newsletter_view_in_browser_url]';
    $newsletterLink1->newsletterId = $this->newsletter->id;
    $newsletterLink1->queueId = $this->queue->id;
    $this->newsletterLink1 = $newsletterLink1->save();
    $newsletterLink2 = NewsletterLink::create();
    $newsletterLink2->hash = 'i1893';
    $newsletterLink2->url = 'http://google.com';
    $newsletterLink2->newsletterId = $this->newsletter->id;
    $newsletterLink2->queueId = $this->queue->id;
    $this->newsletterLink2 = $newsletterLink2->save();
  }

  public function testItRendersNewsletter() {
    $renderedBody = $this->viewInBrowser->renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $queue = false,
      $preview = false
    );
    expect($renderedBody)->regExp('/Rendered newsletter/');
  }

  public function testItReusesRenderedNewsletterBodyWhenQueueExists() {
    $emoji = $this->make(
      Emoji::class,
      ['decodeEmojisInBody' => Expected::once(function ($params) {
        return $params;
      })]
    );
    $viewInBrowser = new ViewInBrowserRenderer($emoji, false);
    $renderedBody = $viewInBrowser->renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($renderedBody)->regExp('/Newsletter from queue/');
  }

  public function testItConvertsShortcodes() {
    $settings = SettingsController::getInstance();
    $settings->set('tracking.enabled', false);
    $renderedBody = $this->viewInBrowser->renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($renderedBody)->contains('Hello, First');
    expect($renderedBody)->contains(Router::NAME . '&endpoint=view_in_browser');
  }

  public function testItRewritesLinksToRouterEndpointWhenTrackingIsEnabled() {
    $settings = SettingsController::getInstance();
    $settings->set('tracking.enabled', true);
    $viewInBrowser = new ViewInBrowserRenderer($this->emoji, true);
    $queue = $this->queue;
    $queue->newsletterRenderedBody = $this->queueRenderedNewsletterWithTracking;
    $renderedBody = $viewInBrowser->renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $queue,
      $preview = false
    );
    expect($renderedBody)->contains(Router::NAME . '&endpoint=track');
  }

  public function testItConvertsHashedLinksToUrlsWhenPreviewIsEnabledAndNewsletterWasSent() {
    $queue = $this->queue;
    $queue->newsletterRenderedBody = $this->queueRenderedNewsletterWithTracking;
    $renderedBody = $this->viewInBrowser->renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $queue,
      $preview = true
    );
    // hashed link should be replaced with a URL
    expect($renderedBody)->notContains('[mailpoet_click_data]');
    expect($renderedBody)->contains('<a href="http://google.com">');
  }

  public function testRemovesOpenTrackingTagWhenPreviewIsEnabledAndNewsletterWasSent() {
    $queue = $this->queue;
    $queue->newsletterRenderedBody = $this->queueRenderedNewsletterWithTracking;
    $renderedBody = $this->viewInBrowser->renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $queue,
      $preview = true
    );
    // open tracking data tag should be removed
    expect($renderedBody)->notContains('[mailpoet_open_data]');
    expect($renderedBody)->contains('<img alt="" class="" src="">');
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
