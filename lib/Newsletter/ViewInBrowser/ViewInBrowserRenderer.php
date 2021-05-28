<?php

namespace MailPoet\Newsletter\ViewInBrowser;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Emoji;

class ViewInBrowserRenderer {
  /** @var Emoji */
  private $emoji;

  /** @var bool */
  private $isTrackingEnabled;

  /** @var Renderer */
  private $renderer;

  /** @var Shortcodes */
  private $shortcodes;

  /** @var Links */
  private $links;

  public function __construct(
    Emoji $emoji,
    SettingsController $settings,
    Shortcodes $shortcodes,
    Renderer $renderer,
    Links $links
  ) {
    $this->emoji = $emoji;
    $this->isTrackingEnabled = $settings->get('tracking.enabled');
    $this->renderer = $renderer;
    $this->shortcodes = $shortcodes;
    $this->links = $links;
  }

  public function render(
    bool $isPreview,
    Newsletter $newsletter,
    SubscriberEntity $subscriber = null,
    SendingQueueEntity $queue = null
  ) {
    $wpUserPreview = $isPreview;
    if ($queue && $queue->getNewsletterRenderedBody()) {
      $body = $queue->getNewsletterRenderedBody();
      if (is_array($body)) {
        $newsletterBody = $body['html'];
      } else {
        $newsletterBody = '';
      }
      $newsletterBody = $this->emoji->decodeEmojisInBody($newsletterBody);
      // rendered newsletter body has shortcodes converted to links; we need to
      // isolate "view in browser", "unsubscribe" and "manage subscription" links
      // and convert them to shortcodes, which later will be replaced with "#" when
      // newsletter is previewed
      if ($wpUserPreview && preg_match($this->links->getLinkRegex(), $newsletterBody)) {
        $newsletterBody = $this->links->convertHashedLinksToShortcodesAndUrls(
          $newsletterBody,
          $queue->getId(),
          $convertAll = true
        );
        // remove open tracking link
        $newsletterBody = str_replace(Links::DATA_TAG_OPEN, '', $newsletterBody);
      }
    } else {
      if ($wpUserPreview) {
        $newsletterBody = $this->renderer->renderAsPreview($newsletter, 'html');
      } else {
        $newsletterBody = $this->renderer->render($newsletter, $sendingTask = null, 'html');
      }
    }
    $this->prepareShortcodes(
      $newsletter,
      $subscriber ?: false,
      $queue ?: false,
      $wpUserPreview
    );
    $renderedNewsletter = $this->shortcodes->replace($newsletterBody);
    if (!$wpUserPreview && $queue && $subscriber && $this->isTrackingEnabled) {
      $renderedNewsletter = $this->links->replaceSubscriberData(
        $subscriber->getId(),
        $queue->getId(),
        $renderedNewsletter
      );
    }
    return $renderedNewsletter;
  }

  /** this is here to prepare entities for the shortcodes library, when this whole file uses doctrine, this can be deleted */
  private function prepareShortcodes($newsletter, $subscriber, $queue, $wpUserPreview) {
    /** @var NewslettersRepository $newsletterRepository */
    $newsletterRepository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);

    if ($queue instanceof SendingQueueEntity) {
      $this->shortcodes->setQueue($queue);
    }
    if ($newsletter instanceof Newsletter) {
      $newsletter = $newsletterRepository->findOneById($newsletter->id);
    }
    if ($newsletter instanceof NewsletterEntity) {
      $this->shortcodes->setNewsletter($newsletter);
    }

    $this->shortcodes->setWpUserPreview($wpUserPreview);
    if ($subscriber instanceof SubscriberEntity) {
      $this->shortcodes->setSubscriber($subscriber);
    }
  }
}
