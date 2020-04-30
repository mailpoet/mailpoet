<?php

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Models\NewsletterLink as NewsletterLinkModel;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Router\Endpoints\Track;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Util\Helpers;

class Links {
  public static function process($renderedNewsletter, $newsletter, $queue) {
    list($renderedNewsletter, $links) =
      self::hashAndReplaceLinks($renderedNewsletter, $newsletter->id, $queue->id);
    self::saveLinks($links, $newsletter, $queue);
    return $renderedNewsletter;
  }

  public static function hashAndReplaceLinks($renderedNewsletter, $newsletterId, $queueId) {
    // join HTML and TEXT rendered body into a text string
    $content = Helpers::joinObject($renderedNewsletter);
    list($content, $links) = NewsletterLinks::process($content, $newsletterId, $queueId);
    $links = NewsletterLinks::ensureInstantUnsubscribeLink($links);
    // split the processed body with hashed links back to HTML and TEXT
    list($renderedNewsletter['html'], $renderedNewsletter['text'])
      = Helpers::splitObject($content);
    return [
      $renderedNewsletter,
      $links,
    ];
  }

  public static function saveLinks($links, $newsletter, $queue) {
    return NewsletterLinks::save($links, $newsletter->id, $queue->id);
  }

  public static function getUnsubscribeUrl($queue, $subscriberId) {
    $subscriber = Subscriber::where('id', $subscriberId)->findOne();
    $settings = SettingsController::getInstance();
    if ((boolean)$settings->get('tracking.enabled')) {
      $linkHash = NewsletterLinkModel::where('queue_id', $queue->id)
        ->where('url', NewsletterLinkModel::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE)
        ->findOne();
      if (!$linkHash instanceof NewsletterLinkModel) {
        return '';
      }
      $linkTokens = new LinkTokens;
      $data = NewsletterLinks::createUrlDataObject(
        $subscriber->id,
        $linkTokens->getToken($subscriber),
        $queue->id,
        $linkHash->hash,
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
