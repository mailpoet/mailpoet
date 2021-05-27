<?php

namespace MailPoet\Newsletter\ViewInBrowser;

use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\SubscribersRepository;

class ViewInBrowserController {
  /** @var LinkTokens */
  private $linkTokens;

  /** @var ViewInBrowserRenderer */
  private $viewInBrowserRenderer;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  public function __construct(
    LinkTokens $linkTokens,
    ViewInBrowserRenderer $viewInBrowserRenderer,
    SendingQueuesRepository $sendingQueuesRepository,
    SubscribersRepository $subscribersRepository
  ) {
    $this->linkTokens = $linkTokens;
    $this->viewInBrowserRenderer = $viewInBrowserRenderer;
    $this->subscribersRepository = $subscribersRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
  }

  public function view(array $data) {
    $data = NewsletterUrl::transformUrlDataObject($data);
    $isPreview = !empty($data['preview']);
    $newsletter = $this->getNewsletter($data);
    $subscriber = $this->getSubscriber($data);

    // if this is a preview and subscriber does not exist,
    // attempt to set subscriber to the current logged-in WP user
    if (!$subscriber && $isPreview) {
      $subscriber = $this->subscribersRepository->getCurrentWPUser();
    }

    // if queue and subscriber exist, subscriber must have received the newsletter
    $queue = $this->getQueue($newsletter, $data);
    if (!$isPreview && $queue && $subscriber && !$this->sendingQueuesRepository->isSubscriberProcessed($queue, $subscriber)) {
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

    $subscriber = $this->subscribersRepository->findOneById($data['subscriber_id']);
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

  private function getQueue(Newsletter $newsletter, array $data): ?SendingQueueEntity {
    // queue is optional; try to find it if it's not defined and this is not a welcome email
    if ($newsletter->type === Newsletter::TYPE_WELCOME) {
      return null;
    }

    // reset queue when automatic email is being previewed
    if ($newsletter->type === Newsletter::TYPE_AUTOMATIC && !empty($data['preview'])) {
      return null;
    }

    return !empty($data['queue_id'])
      ? $this->sendingQueuesRepository->findOneById($data['queue_id'])
      : $this->sendingQueuesRepository->findOneBy(['newsletter' => $newsletter->id]);
  }
}
