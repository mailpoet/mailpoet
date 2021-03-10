<?php

namespace MailPoet\Newsletter\ViewInBrowser;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending;
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

  public function __construct(
    Emoji $emoji,
    SettingsController $settings,
    Shortcodes $shortcodes,
    Renderer $renderer
  ) {
    $this->emoji = $emoji;
    $this->isTrackingEnabled = $settings->get('tracking.enabled');
    $this->renderer = $renderer;
    $this->shortcodes = $shortcodes;
  }

  public function render(
    bool $isPreview,
    Newsletter $newsletter,
    SubscriberEntity $subscriber = null,
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
      $renderedNewsletter = Links::replaceSubscriberData(
        $subscriber->getId(),
        $queue->id,
        $renderedNewsletter
      );
    }
    return $renderedNewsletter;
  }

  /** this is here to prepare entities for the shortcodes library, when this whole file uses doctrine, this can be deleted */
  private function prepareShortcodes($newsletter, $subscriber, $queue, $wpUserPreview) {
    /** @var SendingQueuesRepository $sendingQueueRepository */
    $sendingQueueRepository = ContainerWrapper::getInstance()->get(SendingQueuesRepository::class);
    /** @var NewslettersRepository $newsletterRepository */
    $newsletterRepository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);
    /** @var SubscribersRepository $subscribersRepository */
    $subscribersRepository = ContainerWrapper::getInstance()->get(SubscribersRepository::class);

    if ($queue instanceof Sending || $queue instanceof SendingQueue) {
      $queue = $sendingQueueRepository->findOneById($queue->id);
    }
    if ($queue instanceof SendingQueueEntity) {
      $this->shortcodes->setQueue($queue);
    }
    if ($newsletter instanceof Newsletter) {
      $newsletter = $newsletterRepository->findOneById($newsletter->id);
    }
    if ($newsletter instanceof NewsletterEntity) {
      $this->shortcodes->setNewsletter($newsletter);
    }
    if ($subscriber instanceof Subscriber) {
      $subscriber = $subscribersRepository->findOneById($subscriber->id);
    }
    $this->shortcodes->setWpUserPreview($wpUserPreview);
    if ($subscriber instanceof SubscriberEntity) {
      $this->shortcodes->setSubscriber($subscriber);
    }
  }
}
