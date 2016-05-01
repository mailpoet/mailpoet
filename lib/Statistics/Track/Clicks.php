<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Shortcodes\Categories\Link;
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
    $newsletter = $this->getNewsletter($newsletter_id);
    $subscriber = $this->getSubscriber($subscriber_id);
    $queue = $this->getQueue($queue_id);
    $link = $this->getLink($hash);
    if(!$subscriber || !$newsletter || !$link || !$queue) {
      $this->abort();
    }
    $statistics = StatisticsClicks::where('link_id', $link['id'])
      ->where('subscriber_id', $subscriber['id'])
      ->where('newsletter_id', $newsletter['id'])
      ->where('queue_id', $queue['id'])
      ->findOne();
    if(!$statistics) {
      // track open event in case it did not register
      $open = new Opens($url, $display_image = false);
      $open->track();
      $statistics = StatisticsClicks::create();
      $statistics->newsletter_id = $newsletter['id'];
      $statistics->link_id = $link['id'];
      $statistics->subscriber_id = $subscriber['id'];
      $statistics->queue_id = $queue['id'];
      $statistics->count = 1;
      $statistics->save();
    } else {
      $statistics->count++;
      $statistics->save();
    }
    $url = $this->processUrl($link['url'], $newsletter, $subscriber, $queue);
    header('Location: ' . $url, true, 302);
    exit;
  }

  function getNewsletter($newsletter_id) {
    $newsletter = Newsletter::findOne($newsletter_id);
    return ($newsletter) ? $newsletter->asArray() : $newsletter;
  }

  function getSubscriber($subscriber_id) {
    $subscriber = Subscriber::findOne($subscriber_id);
    return ($subscriber) ? $subscriber->asArray() : $subscriber;
  }

  function getQueue($queue_id) {
    $queue = SendingQueue::findOne($queue_id);
    return ($queue) ? $queue->asArray() : $queue;
  }

  function getLink($hash) {
    $link = NewsletterLink::where('hash', $hash)
      ->findOne();
    return ($link) ? $link->asArray() : $link;
  }

  function processUrl($url, $newsletter, $subscriber, $queue) {
    if(preg_match('/\[link:(?P<action>.*?)\]/', $url, $shortcode)) {
      if(!$shortcode['action']) $this->abort();
      $url = Link::processShortcodeAction(
        $shortcode['action'],
        $newsletter,
        $subscriber,
        $queue
      );
      if (!$url) $this->abort();
    }
    return $url;
  }

  private function abort() {
    header('HTTP/1.0 404 Not Found');
    exit;
  }
}