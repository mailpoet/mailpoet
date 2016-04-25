<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\NewsletterLink;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\Subscriber;
use MailPoet\Subscription\Url as SubscriptionUrl;

if(!defined('ABSPATH')) exit;

class Clicks {
  public $url;

  function __construct($url) {
    $this->url = $url;
  }

  function track($url = false) {
    $url = ($url) ? $url : $this->url;
    if(!preg_match('/\d+-\d+-\d+-[a-zA-Z0-9]/', $url)) $this->abort();
    list ($newsletter_id, $subscriber_id, $queue_id, $hash) = explode('-', $url);
    $subscriber = Subscriber::findOne($subscriber_id);
    $link = NewsletterLink::where('hash', $hash)
      ->findOne();
    if(!$subscriber) return;
    if(!$link) $this->abort();
    $statistics = StatisticsClicks::where('link_id', $link->id)
      ->where('subscriber_id', $subscriber_id)
      ->where('newsletter_id', $newsletter_id)
      ->where('queue_id', $queue_id)
      ->findOne();
    if(!$statistics) {
      // track open action in case it did not register
      $opens = new Opens($url, $display_image = false);
      $opens->track();
      $statistics = StatisticsClicks::create();
      $statistics->newsletter_id = $newsletter_id;
      $statistics->link_id = $link->id;
      $statistics->subscriber_id = $subscriber_id;
      $statistics->queue_id = $queue_id;
      $statistics->count = 1;
      $statistics->save();
    } else {
      $statistics->count++;
      $statistics->save();
    }
    $url = (preg_match('/\[subscription:.*?\]/', $link->url)) ?
      $this->processSubscriptionUrl($link->url, $subscriber) :
      $link->url;
    header('Location: ' . $url, true, 302);
  }

  function processSubscriptionUrl($url, $subscriber) {
    preg_match('/\[subscription:(.*?)\]/', $url, $match);
    $action = $match[1];
    if(preg_match('/unsubscribe/', $action)) {
      $url = SubscriptionUrl::getUnsubscribeUrl($subscriber);
    }
    if(preg_match('/manage/', $action)) {
      $url = SubscriptionUrl::getManageUrl($subscriber);
    }
    return $url;
  }

  private function abort() {
    header('HTTP/1.0 404 Not Found');
    exit;
  }
}