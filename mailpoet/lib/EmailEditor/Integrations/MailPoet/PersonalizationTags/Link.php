<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\SubscriptionUrlFactory;

class Link {

  private SubscriptionUrlFactory $subscriptionUrlFactory;
  private SubscribersRepository $subscribersRepository;
  private SendingQueuesRepository $sendingQueuesRepository;
  private NewsletterUrl $newsletterUrl;
  private NewslettersRepository $newslettersRepository;

  public function __construct(
    SubscriptionUrlFactory $subscriptionUrlFactory,
    SubscribersRepository $subscribersRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    NewslettersRepository $newslettersRepository,
    NewsletterUrl $newsletterUrl
  ) {
    $this->subscriptionUrlFactory = $subscriptionUrlFactory;
    $this->subscribersRepository = $subscribersRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterUrl = $newsletterUrl;
  }

  public function getSubscriptionUnsubscribeUrl(array $context, array $args = []): string {
    $isPreview = $context['is_preview'] ?? false;
    $subscriber = !$isPreview ? $this->getSubscriber($context) : null;
    $queue = $this->getSendingQueue($context);

    return $this->subscriptionUrlFactory->getConfirmUnsubscribeUrl(
      $subscriber,
      $queue ? $queue->getId() : null
    );
  }

  public function getSubscriptionManageUrl(array $context, array $args = []): string {
    $isPreview = $context['is_preview'] ?? false;
    $subscriber = !$isPreview ? $this->getSubscriber($context) : null;

    return $this->subscriptionUrlFactory->getManageUrl(
      $subscriber
    );
  }

  public function getNewsletterViewInBrowserUrl(array $context, array $args = []): string {
    $isPreview = $context['is_preview'] ?? false;
    $subscriber = !$isPreview ? $this->getSubscriber($context) : null;
    $queue = $this->getSendingQueue($context);
    $newsletter = $this->getNewsletter($context);

    return $this->newsletterUrl->getViewInBrowserUrl(
      $newsletter,
      $subscriber,
      $queue,
      $isPreview
    );
  }

  private function getNewsletter(array $context): ?NewsletterEntity {
    $newsletterId = $context['newsletter_id'] ?? null;
    return $newsletterId ? $this->newslettersRepository->findOneById($newsletterId) : null;
  }

  private function getSendingQueue(array $context): ?SendingQueueEntity {
    $queueId = $context['queue_id'] ?? null;
    return $queueId ? $this->sendingQueuesRepository->findOneById($queueId) : null;
  }

  private function getSubscriber(array $context): ?SubscriberEntity {
    $subscriberEmail = $context['recipient_email'] ?? null;
    return $subscriberEmail ? $this->subscribersRepository->findOneBy(['email' => $subscriberEmail]) : null;
  }
}
