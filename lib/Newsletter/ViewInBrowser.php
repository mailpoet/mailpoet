<?php

namespace MailPoet\Newsletter;

use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\WP\Emoji;

class ViewInBrowser {
  /** @var Emoji */
  private $emoji;

  /** @var bool */
  private $isTrackingEnabled;

  public function __construct(Emoji $emoji, $isTrackingEnabled) {
    $this->isTrackingEnabled = $isTrackingEnabled;
    $this->emoji = $emoji;
  }

  public function view(array $data) {
    $wpUserPreview = (
      ($data['subscriber'] && $data['subscriber']->isWPUser() && $data['preview'])
      ||
      ($data['preview'] && $data['newsletter_hash'])
    );
    return $this->renderNewsletter(
      $data['newsletter'],
      $data['subscriber'],
      $data['queue'],
      $wpUserPreview
    );
  }

  public function renderNewsletter($newsletter, $subscriber, $queue, $wpUserPreview) {
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
      $renderer = new Renderer($newsletter, $wpUserPreview);
      $newsletterBody = $renderer->render('html');
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
