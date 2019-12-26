<?php

namespace MailPoet\Test\Newsletter;

use Codeception\Stub\Expected;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\ViewInBrowser;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Emoji;
use MailPoetVendor\Idiorm\ORM;

class ViewInBrowserTest extends \MailPoetTest {
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
    $this->queue_rendered_newsletter_without_tracking = [
      'html' => '<p>Newsletter from queue. Hello, [subscriber:firstname | default:reader]. <a href="[link:newsletter_view_in_browser_url]">Unsubscribe</a> or visit <a href="http://google.com">Google</a></p>',
      'text' => 'test',
    ];
    $this->queue_rendered_newsletter_with_tracking = [
      'html' => '<p>Newsletter from queue. Hello, [subscriber:firstname | default:reader]. <a href="' . Links::DATA_TAG_CLICK . '-90e56">Unsubscribe</a> or visit <a href="' . Links::DATA_TAG_CLICK . '-i1893">Google</a><img alt="" class="" src="' . Links::DATA_TAG_OPEN . '"></p>',
      'text' => 'test',
    ];
    $this->emoji = new Emoji();
    $this->view_in_browser = new ViewInBrowser($this->emoji, false);
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
    $queue = SendingTask::create();
    $queue->newsletter_id = $newsletter->id;
    $queue->newsletter_rendered_body = $this->queue_rendered_newsletter_without_tracking;
    $queue->setSubscribers([$subscriber->id]);
    $this->queue = $queue->save();
    // create newsletter link associations
    $newsletter_link_1 = NewsletterLink::create();
    $newsletter_link_1->hash = '90e56';
    $newsletter_link_1->url = '[link:newsletter_view_in_browser_url]';
    $newsletter_link_1->newsletter_id = $this->newsletter->id;
    $newsletter_link_1->queue_id = $this->queue->id;
    $this->newsletter_link_1 = $newsletter_link_1->save();
    $newsletter_link_2 = NewsletterLink::create();
    $newsletter_link_2->hash = 'i1893';
    $newsletter_link_2->url = 'http://google.com';
    $newsletter_link_2->newsletter_id = $this->newsletter->id;
    $newsletter_link_2->queue_id = $this->queue->id;
    $this->newsletter_link_2 = $newsletter_link_2->save();
  }

  public function testItRendersNewsletter() {
    $rendered_body = $this->view_in_browser->renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $queue = false,
      $preview = false
    );
    expect($rendered_body)->regExp('/Rendered newsletter/');
  }

  public function testItReusesRenderedNewsletterBodyWhenQueueExists() {
    $emoji = $this->make(
      Emoji::class,
      ['decodeEmojisInBody' => Expected::once(function ($params) {
        return $params;
      })],
      $this
    );
    $view_in_browser = new ViewInBrowser($emoji, false);
    $rendered_body = $view_in_browser->renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($rendered_body)->regExp('/Newsletter from queue/');
  }

  public function testItConvertsShortcodes() {
    $settings = SettingsController::getInstance();
    $settings->set('tracking.enabled', false);
    $rendered_body = $this->view_in_browser->renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($rendered_body)->contains('Hello, First');
    expect($rendered_body)->contains(Router::NAME . '&endpoint=view_in_browser');
  }

  public function testItRewritesLinksToRouterEndpointWhenTrackingIsEnabled() {
    $settings = SettingsController::getInstance();
    $settings->set('tracking.enabled', true);
    $view_in_browser = new ViewInBrowser($this->emoji, true);
    $queue = $this->queue;
    $queue->newsletter_rendered_body = $this->queue_rendered_newsletter_with_tracking;
    $rendered_body = $view_in_browser->renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $queue,
      $preview = false
    );
    expect($rendered_body)->contains(Router::NAME . '&endpoint=track');
  }

  public function testItConvertsHashedLinksToUrlsWhenPreviewIsEnabledAndNewsletterWasSent() {
    $queue = $this->queue;
    $queue->newsletter_rendered_body = $this->queue_rendered_newsletter_with_tracking;
    $rendered_body = $this->view_in_browser->renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $queue,
      $preview = true
    );
    // hashed link should be replaced with a URL
    expect($rendered_body)->notContains('[mailpoet_click_data]');
    expect($rendered_body)->contains('<a href="http://google.com">');
  }

  public function testRemovesOpenTrackingTagWhenPreviewIsEnabledAndNewsletterWasSent() {
    $queue = $this->queue;
    $queue->newsletter_rendered_body = $this->queue_rendered_newsletter_with_tracking;
    $rendered_body = $this->view_in_browser->renderNewsletter(
      $this->newsletter,
      $this->subscriber,
      $queue,
      $preview = true
    );
    // open tracking data tag should be removed
    expect($rendered_body)->notContains('[mailpoet_open_data]');
    expect($rendered_body)->contains('<img alt="" class="" src="">');
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
