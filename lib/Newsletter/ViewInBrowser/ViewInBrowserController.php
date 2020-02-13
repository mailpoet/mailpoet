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
    $newsletter = $this->getNewsletter($data);
    $subscriber = $this->getSubscriber($data);

    // if this is a preview and subscriber does not exist,
    // attempt to set subscriber to the current logged-in WP user
    if (!$subscriber && $isPreview) {
      $subscriber = Subscriber::getCurrentWPUser() ?: null;
    }

    // allow users with permission to manage emails to preview any newsletter
    $canView = $isPreview && $this->accessControl->validatePermission(AccessControl::PERMISSION_MANAGE_EMAILS);

    // if queue and subscriber exist, subscriber must have received the newsletter
    $queue = $this->getQueue($newsletter, $data);
    if (!$canView && $queue && $subscriber && !$queue->isSubscriberProcessed($subscriber->id)) {
      throw new \InvalidArgumentException("Subscriber did not receive the newsletter yet");
    }

    return $this->viewInBrowserRenderer->render($isPreview, $newsletter, $subscriber, $queue);
  }

  private function getNewsletter(array $data) {
    // newsletter - ID is mandatory, hash must be set and valid
    if (empty($data['newsletter_id'])) {
      throw new \InvalidArgumentException("Missing 'newsletter_id'");
    }
    if (empty($data['newsletter_hash'])) {
      throw new \InvalidArgumentException("Missing 'newsletter_hash'");
    }

    $newsletter = Newsletter::findOne($data['newsletter_id']) ?: null;
    if (!$newsletter) {
      throw new \InvalidArgumentException("Invalid 'newsletter_id'");
    }

    if ($data['newsletter_hash'] !== $newsletter->hash) {
      throw new \InvalidArgumentException("Invalid 'newsletter_hash'");
    }
    return $newsletter;
  }

  private function getSubscriber(array $data) {
    // subscriber is optional; if exists, token must validate
    if (empty($data['subscriber_id'])) {
      return null;
    }

    $subscriber = Subscriber::findOne($data['subscriber_id']) ?: null;
    if (!$subscriber) {
      return null;
    }

    if (empty($data['subscriber_token'])) {
      throw new \InvalidArgumentException("Missing 'subscriber_token'");
    }

    if (!$this->linkTokens->verifyToken($subscriber, $data['subscriber_token'])) {
      throw new \InvalidArgumentException("Invalid 'subscriber_token'");
    }
    return $subscriber;
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
