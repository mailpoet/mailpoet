<?php

namespace MailPoet\Newsletter\ViewInBrowser;

use Codeception\Stub\Expected;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Emoji;
use MailPoetVendor\Idiorm\ORM;

class ViewInBrowserRendererTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var ViewInBrowserRenderer */
  private $viewInBrowserRenderer;

  /** @var Newsletter */
  public $newsletter;

  /** @var SendingTask */
  private $sendingTask;

  /** @var Subscriber */
  private $subscriber;

  /** @var mixed[] */
  private $queueRenderedNewsletterWithTracking;

  /** @var mixed[] */
  private $queueRenderedNewsletterWithoutTracking;

  public function _before() {
    $newsletterData = [
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

    // create newsletter
    $newsletter = Newsletter::create();
    $newsletter->hydrate($newsletterData);
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
    $this->sendingTask = $queue->save();

    // create newsletter link associations
    $newsletterLink1 = NewsletterLink::create();
    $newsletterLink1->hash = '90e56';
    $newsletterLink1->url = '[link:newsletter_view_in_browser_url]';
    $newsletterLink1->newsletterId = $this->newsletter->id;
    $newsletterLink1->queueId = $this->sendingTask->id;
    $newsletterLink1->save();

    $newsletterLink2 = NewsletterLink::create();
    $newsletterLink2->hash = 'i1893';
    $newsletterLink2->url = 'http://google.com';
    $newsletterLink2->newsletterId = $this->newsletter->id;
    $newsletterLink2->queueId = $this->sendingTask->id;
    $newsletterLink2->save();

    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->viewInBrowserRenderer = $this->diContainer->get(ViewInBrowserRenderer::class);
  }

  public function testItRendersNewsletter() {
    $renderedBody = $this->viewInBrowserRenderer->render(
      $preview = false,
      $this->newsletter,
      $this->subscriber,
      $queue = null
    );
    expect($renderedBody)->regExp('/Rendered newsletter/');
  }

  public function testItReusesRenderedNewsletterBodyWhenQueueExists() {
    $emoji = $this->make(Emoji::class, [
      'decodeEmojisInBody' => Expected::once(function ($params) {
        return $params;
      }),
    ]);
    $this->settings->set('tracking.enabled', false);
    $viewInBrowser = new ViewInBrowserRenderer(
      $emoji,
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Renderer::class)
    );
    $renderedBody = $viewInBrowser->render(
      $preview = false,
      $this->newsletter,
      $this->subscriber,
      $this->sendingTask->queue()
    );
    expect($renderedBody)->regExp('/Newsletter from queue/');
  }

  public function testItConvertsShortcodes() {
    $this->settings->set('tracking.enabled', false);
    $renderedBody = $this->viewInBrowserRenderer->render(
      $preview = false,
      $this->newsletter,
      $this->subscriber,
      $this->sendingTask->queue()
    );
    expect($renderedBody)->stringContainsString('Hello, First');
    expect($renderedBody)->stringContainsString(Router::NAME . '&endpoint=view_in_browser');
  }

  public function testItRewritesLinksToRouterEndpointWhenTrackingIsEnabled() {
    $this->settings->set('tracking.enabled', true);
    $queue = $this->sendingTask->queue();
    $queue->newsletterRenderedBody = $this->queueRenderedNewsletterWithTracking;
    $renderedBody = $this->viewInBrowserRenderer->render(
      $preview = false,
      $this->newsletter,
      $this->subscriber,
      $queue
    );
    expect($renderedBody)->stringContainsString(Router::NAME . '&endpoint=track');
  }

  public function testItConvertsHashedLinksToUrlsWhenPreviewIsEnabledAndNewsletterWasSent() {
    $queue = $this->sendingTask->queue();
    $queue->newsletterRenderedBody = $this->queueRenderedNewsletterWithTracking;
    $renderedBody = $this->viewInBrowserRenderer->render(
      $preview = true,
      $this->newsletter,
      $this->subscriber,
      $queue
    );
    // hashed link should be replaced with a URL
    expect($renderedBody)->stringNotContainsString('[mailpoet_click_data]');
    expect($renderedBody)->stringContainsString('<a href="http://google.com">');
  }

  public function testRemovesOpenTrackingTagWhenPreviewIsEnabledAndNewsletterWasSent() {
    $queue = $this->sendingTask->queue();
    $queue->newsletterRenderedBody = $this->queueRenderedNewsletterWithTracking;
    $renderedBody = $this->viewInBrowserRenderer->render(
      $preview = true,
      $this->newsletter,
      $this->subscriber,
      $queue
    );
    // open tracking data tag should be removed
    expect($renderedBody)->stringNotContainsString('[mailpoet_open_data]');
    expect($renderedBody)->stringContainsString('<img alt="" class="" src="">');
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
