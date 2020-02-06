<?php

namespace MailPoet\Newsletter\ViewInBrowser;

use MailPoet\Config\AccessControl;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Subscribers\LinkTokens;

class ViewInBrowserController {
  /** @var AccessControl */
  private $accessControl;

  /** @var LinkTokens */
  private $linkTokens;

  /** @var ViewInBrowserRenderer */
  private $viewInBrowserRenderer;

  public function __construct(
    AccessControl $accessControl,
    LinkTokens $linkTokens,
    ViewInBrowserRenderer $viewInBrowserRenderer
  ) {
    $this->accessControl = $accessControl;
    $this->linkTokens = $linkTokens;
    $this->viewInBrowserRenderer = $viewInBrowserRenderer;
  }

  public function view(array $data) {
    $data = NewsletterUrl::transformUrlDataObject($data);
    $isPreview = !empty($data['preview']);

    // newsletter - ID is mandatory hash must be set and valid
    $newsletter = empty($data['newsletter_id']) ? null : Newsletter::findOne($data['newsletter_id']);
    if (!$newsletter || empty($data['newsletter_hash']) || $data['newsletter_hash'] !== $newsletter->hash) {
      throw new \InvalidArgumentException();
    }

    // subscriber is optional; if exists, token must validate
    $subscriber = empty($data['subscriber_id']) ? null : Subscriber::findOne($data['subscriber_id']);
    $subscriberToken = $data['subscriber_token'] ?? null;
    if ($subscriber && (!$subscriberToken || !$this->linkTokens->verifyToken($subscriber, $subscriberToken))) {
      throw new \InvalidArgumentException();
    }

    // if this is a preview and subscriber does not exist,
    // attempt to set subscriber to the current logged-in WP user
    if (!$subscriber && $isPreview) {
      $subscriber = Subscriber::getCurrentWPUser();
    }

    // allow users with permission to manage emails to preview any newsletter
    $canView = $isPreview && $this->accessControl->validatePermission(AccessControl::PERMISSION_MANAGE_EMAILS);

    // if queue and subscriber exist, subscriber must have received the newsletter
    $queue = $this->getQueue($newsletter, $data);
    if (!$canView && $queue && $subscriber && !$queue->isSubscriberProcessed($subscriber->id)) {
      throw new \InvalidArgumentException();
    }

    return $this->viewInBrowserRenderer->render($isPreview, $newsletter, $subscriber, $queue);
  }

  private function getQueue(Newsletter $newsletter, array $data) {
    // queue is optional; try to find it if it's not defined and this is not a welcome email
    if ($newsletter->type === Newsletter::TYPE_WELCOME) {
      return null;
    }

    // reset queue when automatic email is being previewed
    if ($newsletter->type === Newsletter::TYPE_AUTOMATIC && !empty($data['preview'])) {
      return null;
    }

    $queue = !empty($data['queue_id'])
      ? SendingQueue::findOne($data['queue_id'])
      : SendingQueue::where('newsletter_id', $newsletter->id)->findOne();

    return $queue ?: null;
  }
}
