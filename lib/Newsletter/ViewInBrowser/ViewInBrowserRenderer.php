<?php

namespace MailPoet\Newsletter\ViewInBrowser;

use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Emoji;

class ViewInBrowserRenderer {
  /** @var Emoji */
  private $emoji;

  /** @var bool */
  private $isTrackingEnabled;

  public function __construct(Emoji $emoji, SettingsController $settings) {
    $this->emoji = $emoji;
    $this->isTrackingEnabled = $settings->get('tracking.enabled');
  }

  public function render(
    bool $isPreview,
    Newsletter $newsletter,
    Subscriber $subscriber = null,
    SendingQueue $queue = null
  ) {
    $wpUserPreview = $isPreview;
    if ($queue && $queue->getNewsletterRenderedBody()) {
      $newsletterBody = $queue->getNewsletterRenderedBody('html');
      $newsletterBody = $this->emoji->decodeEmojisInBody($newsletterBody);
      // rendered newsletter body has shortcodes converted to links; we need to
      // isolate "view in browser", "unsubscribe" and "manage subscription" links
      // and convert them to shortcodes, which later will be replaced with "#" when
      // newsletter is previewed
      if ($wpUserPreview && preg_match(Links::getLinkRegex(), $newsletterBody)) {
        $newsletterBody = Links::convertHashedLinksToShortcodesAndUrls(
          $newsletterBody,
          $queueId = $queue->id,
          $convertAll = true
        );
        // remove open tracking link
        $newsletterBody = str_replace(Links::DATA_TAG_OPEN, '', $newsletterBody);
      }
    } else {
      $renderer = new Renderer();
      $newsletterBody = $renderer->render($newsletter, $wpUserPreview, 'html');
    }
    $shortcodes = new Shortcodes(
      $newsletter,
      $subscriber,
      $queue,
      $wpUserPreview
    );
    $renderedNewsletter = $shortcodes->replace($newsletterBody);
    if (!$wpUserPreview && $queue && $subscriber && $this->isTrackingEnabled) {
      $renderedNewsletter = Links::replaceSubscriberData(
        $subscriber->id,
        $queue->id,
        $renderedNewsletter
      );
    }
    return $renderedNewsletter;
  }
}
