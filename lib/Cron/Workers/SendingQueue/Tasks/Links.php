<?php

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Router\Endpoints\Track;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Util\Helpers;

class Links {
  /** @var LinkTokens */
  private $linkTokens;

  /** @var NewsletterLinks */
  private $newsletterLinks;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var NewsletterLinkRepository */
  private $newsletterLinkRepository;

  public function __construct(
    LinkTokens $linkTokens,
    NewsletterLinks $newsletterLinks,
    SubscribersRepository $subscribersRepository,
    NewsletterLinkRepository $newsletterLinkRepository
  ) {
    $this->linkTokens = $linkTokens;
    $this->newsletterLinks = $newsletterLinks;
    $this->subscribersRepository = $subscribersRepository;
    $this->newsletterLinkRepository = $newsletterLinkRepository;
  }

  public function process($renderedNewsletter, $newsletter, $queue) {
    [$renderedNewsletter, $links] = $this->hashAndReplaceLinks($renderedNewsletter, $newsletter->id, $queue->id);
    $this->saveLinks($links, $newsletter, $queue);
    return $renderedNewsletter;
  }

  public function hashAndReplaceLinks($renderedNewsletter, $newsletterId, $queueId) {
    // join HTML and TEXT rendered body into a text string
    $content = Helpers::joinObject($renderedNewsletter);
    [$content, $links] = $this->newsletterLinks->process($content, $newsletterId, $queueId);
    $links = $this->newsletterLinks->ensureInstantUnsubscribeLink($links);
    // split the processed body with hashed links back to HTML and TEXT
    list($renderedNewsletter['html'], $renderedNewsletter['text'])
      = Helpers::splitObject($content);
    return [
      $renderedNewsletter,
      $links,
    ];
  }

  public function saveLinks($links, $newsletter, $queue) {
    return $this->newsletterLinks->save($links, $newsletter->id, $queue->id);
  }

  public function getUnsubscribeUrl($queue, $subscriberId) {
    $subscriber = $this->subscribersRepository->findOneById($subscriberId);
    $settings = SettingsController::getInstance();
    if ((boolean)$settings->get('tracking.enabled') && $subscriber) {
      $linkHash = $this->newsletterLinkRepository->findOneBy(
        [
          'queue' => $queue->id,
          'url' => NewsletterLinkEntity::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE,
        ]
      );

      if (!$linkHash instanceof NewsletterLinkEntity) {
        return '';
      }
      $data = $this->newsletterLinks->createUrlDataObject(
        $subscriber->getId(),
        $this->linkTokens->getToken($subscriber),
        $queue->id,
        $linkHash->getHash(),
        false
      );
      $url = Router::buildRequest(
        Track::ENDPOINT,
        Track::ACTION_CLICK,
        $data
      );
    } else {
      $subscriptionUrlFactory = SubscriptionUrlFactory::getInstance();
      $url = $subscriptionUrlFactory->getUnsubscribeUrl($subscriber, $queue->id);
    }
    return $url;
  }
}
