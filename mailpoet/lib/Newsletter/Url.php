<?php

namespace MailPoet\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Router\Endpoints\ViewInBrowser as ViewInBrowserEndpoint;
use MailPoet\Router\Router;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\SubscribersRepository;

class Url {
  /** @var LinkTokens */
  private $linkTokens;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    LinkTokens $linkTokens,
    SubscribersRepository $subscribersRepository
  ) {
    $this->linkTokens = $linkTokens;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function getViewInBrowserUrl(
    NewsletterEntity $newsletter = null,
    $subscriber = false,
    $queue = false,
    bool $preview = true
  ) {
    if ($subscriber instanceof SubscriberModel) {
      $subscriberEntity = $this->subscribersRepository->findOneById($subscriber->id);
      if ($subscriberEntity instanceof SubscriberEntity) {
        $subscriber->token = $this->linkTokens->getToken($subscriberEntity);
      }
    }
    $data = $this->createUrlDataObject($newsletter, $subscriber, $queue, $preview);
    return Router::buildRequest(
      ViewInBrowserEndpoint::ENDPOINT,
      ViewInBrowserEndpoint::ACTION_VIEW,
      $data
    );
  }

  public function createUrlDataObject(NewsletterEntity $newsletter = null, $subscriber, $queue, $preview) {
    $newsletterId = $newsletter && $newsletter->getId() ? $newsletter->getId() : 0;
    $newsletterHash = $newsletter && $newsletter->getHash() ? $newsletter->getHash() : 0;

    if ($queue instanceof SendingQueueEntity) {
      $sendingQueueId = (!empty($queue->getId())) ? (int)$queue->getId() : 0;
    } else {
      $sendingQueueId = (!empty($queue->id)) ? (int)$queue->id : 0;
    }

    return [
      $newsletterId,
      $newsletterHash,
      (!empty($subscriber->id)) ?
        (int)$subscriber->id :
        0,
      (!empty($subscriber->token)) ?
        $subscriber->token :
        0,
      $sendingQueueId,
      (int)$preview,
    ];
  }

  public function transformUrlDataObject($data) {
    reset($data);
    if (!is_int(key($data))) return $data;
    $transformedData = [];
    $transformedData['newsletter_id'] = (!empty($data[0])) ? $data[0] : false;
    $transformedData['newsletter_hash'] = (!empty($data[1])) ? $data[1] : false;
    $transformedData['subscriber_id'] = (!empty($data[2])) ? $data[2] : false;
    $transformedData['subscriber_token'] = (!empty($data[3])) ? $data[3] : false;
    $transformedData['queue_id'] = (!empty($data[4])) ? $data[4] : false;
    $transformedData['preview'] = (!empty($data[5])) ? $data[5] : false;
    return $transformedData;
  }
}
