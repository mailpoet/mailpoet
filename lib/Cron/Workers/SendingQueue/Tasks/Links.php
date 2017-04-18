<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Util\Helpers;
use MailPoet\Router\Router;
use MailPoet\Models\Setting;
use MailPoet\Subscription\Url;
use MailPoet\Models\Subscriber;
use MailPoet\Router\Endpoints\Track;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Models\NewsletterLink as NewsletterLinkModel;

if(!defined('ABSPATH')) exit;

class Links {
  static function process($rendered_newsletter, $newsletter, $queue) {
    list($rendered_newsletter, $links) =
      self::hashAndReplaceLinks($rendered_newsletter);
    self::saveLinks($links, $newsletter, $queue);
    return $rendered_newsletter;
  }

  static function hashAndReplaceLinks($rendered_newsletter) {
    // join HTML and TEXT rendered body into a text string
    $content = Helpers::joinObject($rendered_newsletter);
    list($content, $links) = NewsletterLinks::process($content);
    // split the processed body with hashed links back to HTML and TEXT
    list($rendered_newsletter['html'], $rendered_newsletter['text'])
      = Helpers::splitObject($content);
    return array(
      $rendered_newsletter,
      $links
    );
  }

  static function saveLinks($links, $newsletter, $queue) {
    return NewsletterLinks::save($links, $newsletter->id, $queue->id);
  }

  static function getUnsubscribeUrl($queue, $subscriber_id) {
    $subscriber = Subscriber::where('id', $subscriber_id)->findOne();
    
    if((boolean)Setting::getValue('tracking.enabled')) {
      $link_hash = NewsletterLinkModel::where('queue_id', $queue->id)
        ->where('url', '[link:subscription_unsubscribe_url]')
        ->findOne()
        ->hash;
      $data = NewsletterLinks::createUrlDataObject(
        $subscriber->id, 
        $subscriber->email,
        $queue->id, 
        $link_hash, 
        false
      );
      $url = Router::buildRequest(
        Track::ENDPOINT,
        Track::ACTION_CLICK,
        $data
      );
    } else {
      $url = Url::getUnsubscribeUrl($subscriber);
    }
    return $url;
  }
}
