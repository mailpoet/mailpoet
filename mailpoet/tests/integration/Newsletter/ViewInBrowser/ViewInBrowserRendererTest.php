<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\ViewInBrowser;

use Codeception\Stub\Expected;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoet\WP\Emoji;

class ViewInBrowserRendererTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var ViewInBrowserRenderer */
  private $viewInBrowserRenderer;

  /** @var NewsletterEntity */
  public $newsletter;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var mixed[] */
  private $queueRenderedNewsletterWithTracking;

  /** @var mixed[] */
  private $queueRenderedNewsletterWithoutTracking;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var NewslettersRepository */
  private $newsletterRepository;

  /** @var ScheduledTaskEntity */
  private $scheduledTask;

  /** @var SendingQueueEntity */
  private $sendingQueue;

  public function _before() {
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->newsletterRepository = $this->diContainer->get(NewslettersRepository::class);
    $scheduledTaskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
    $newsletterBody =
      json_decode(
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
      }',
        true
      );
    $this->queueRenderedNewsletterWithoutTracking = [
      'html' => '<p>Newsletter from queue. Hello, [subscriber:firstname | default:reader]. <a href="[link:newsletter_view_in_browser_url]">Unsubscribe</a> or visit <a href="http://google.com">Google</a></p>',
      'text' => 'test',
    ];
    $this->queueRenderedNewsletterWithTracking = [
      'html' => '<p>Newsletter from queue. Hello, [subscriber:firstname | default:reader]. <a href="' . Links::DATA_TAG_CLICK . '-90e56">Unsubscribe</a> or visit <a href="' . Links::DATA_TAG_CLICK . '-i1893">Google</a><img alt="" class="" src="' . Links::DATA_TAG_OPEN . '"></p>',
      'text' => 'test',
    ];

    // create newsletter
    $newsletter = (new Newsletter())
      ->withBody($newsletterBody)
      ->create();
    $this->newsletter = $newsletter;

    // create subscriber
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('test@example.com');
    $subscriber->setFirstName('First');
    $subscriber->setLastName('Last');
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();
    $this->subscriber = $subscriber;

    // create queue
    $this->scheduledTask = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, null);
    $this->sendingQueue = (new SendingQueueFactory())->create($this->scheduledTask, $newsletter);
    $this->sendingQueue->setNewsletterRenderedBody($this->queueRenderedNewsletterWithoutTracking);
    $scheduledTaskSubscribersRepository->setSubscribers($this->scheduledTask, [$subscriber->getId()]);
    $this->newsletterRepository->refresh($newsletter);
    $this->newsletter = $newsletter;

    // create newsletter link associations
    $newsletterLinkFactory = new NewsletterLink($newsletter);
    $newsletterLinkFactory
      ->withUrl('[link:newsletter_view_in_browser_url]')
      ->withHash('90e56')
      ->create();
    $newsletterLinkFactory
      ->withUrl('http://google.com')
      ->withHash('i1893')
      ->create();

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
    verify($renderedBody)->stringMatchesRegExp('/Rendered newsletter/');
  }

  public function testItReusesRenderedNewsletterBodyWhenQueueExists() {
    $emoji = $this->make(Emoji::class, [
      'decodeEmojisInBody' => Expected::once(function ($params) {
        return $params;
      }),
    ]);
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $viewInBrowser = new ViewInBrowserRenderer(
      $emoji,
      $this->diContainer->get(TrackingConfig::class),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Renderer::class),
      $this->diContainer->get(Links::class)
    );
    $renderedBody = $viewInBrowser->render(
      $preview = false,
      $this->newsletter,
      $this->subscriber,
      $this->sendingQueue
    );
    verify($renderedBody)->stringMatchesRegExp('/Newsletter from queue/');
  }

  public function testItConvertsShortcodes() {
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $renderedBody = $this->viewInBrowserRenderer->render(
      $preview = false,
      $this->newsletter,
      $this->subscriber,
      $this->sendingQueue
    );
    verify($renderedBody)->stringContainsString('Hello, First');
    verify($renderedBody)->stringContainsString(Router::NAME . '&endpoint=view_in_browser');
  }

  public function testItRewritesLinksToRouterEndpointWhenTrackingIsEnabled() {
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_PARTIAL);
    $queue = $this->sendingQueue;
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $queue->setNewsletterRenderedBody($this->queueRenderedNewsletterWithTracking);
    $renderedBody = $this->viewInBrowserRenderer->render(
      $preview = false,
      $this->newsletter,
      $this->subscriber,
      $queue
    );
    verify($renderedBody)->stringContainsString(Router::NAME . '&endpoint=track');
  }

  public function testItConvertsHashedLinksToUrlsWhenPreviewIsEnabledAndNewsletterWasSent() {
    $queue = $this->sendingQueue;
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $queue->setNewsletterRenderedBody($this->queueRenderedNewsletterWithTracking);
    $renderedBody = $this->viewInBrowserRenderer->render(
      $preview = true,
      $this->newsletter,
      $this->subscriber,
      $queue
    );
    // hashed link should be replaced with a URL
    verify($renderedBody)->stringNotContainsString('[mailpoet_click_data]');
    verify($renderedBody)->stringContainsString('<a href="http://google.com">');
  }

  public function testRemovesOpenTrackingTagWhenPreviewIsEnabledAndNewsletterWasSent() {
    $queue = $this->sendingQueue;
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $queue->setNewsletterRenderedBody($this->queueRenderedNewsletterWithTracking);
    $renderedBody = $this->viewInBrowserRenderer->render(
      $preview = true,
      $this->newsletter,
      $this->subscriber,
      $queue
    );
    // open tracking data tag should be removed
    verify($renderedBody)->stringNotContainsString('[mailpoet_open_data]');
    verify($renderedBody)->stringContainsString('<img alt="" class="" src="">');
  }
}
