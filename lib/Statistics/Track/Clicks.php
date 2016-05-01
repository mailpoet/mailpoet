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
      // track open event in case it did not register
      $this->trackOpen($url);
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
    $is_this_subscription = (preg_match('/\[link:(?P<action>.*?)\]/', $link->url, $action));
    $url = ($is_this_subscription) ?
      $this->getSubscriptionUrl($link->url, $subscriber, $queue_id, $newsletter_id) :
      $link->url;
    header('Location: ' . $url, true, 302);
    exit;
  }

  function getSubscriptionUrl(
    $subscription_action, $subscriber, $queue_id, $newsletter_id
  ) {
    if(!isset($subscription_action['action'])) self::abort();
    switch($subscription_action['action']) {
      case 'unsubscribe':
        // track unsubscribe event
        $this->trackUnsubscribe($subscriber->id, $queue_id, $newsletter_id);
        $url = SubscriptionUrl::getUnsubscribeUrl($subscriber);
        break;
      case 'manage':
        $url = SubscriptionUrl::getManageUrl($subscriber);
        break;
    }
    return $url;
  }

  function trackUnsubscribe($subscriber, $queue, $newsletter) {
    $unsubscribe = new Unsubscribes();
    $unsubscribe->track($subscriber, $queue, $newsletter);
  }

  function trackOpen($url) {
    $open = new Opens($url, $display_image = false);
    $open->track();
  }

  private function abort() {
    header('HTTP/1.0 404 Not Found');
    exit;
  }
}