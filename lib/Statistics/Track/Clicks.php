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
  public $data;

  function __construct($data) {
    $this->data = $data;
  }

  function track($data = false) {
    $data = ($data) ? $data : $this->data;
    $newsletter = $this->getNewsletter($data['newsletter']);
    $subscriber = $this->getSubscriber($data['subscriber']);
    $queue = $this->getQueue($data['queue']);
    $link = $this->getLink($data['hash']);
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
      $open = new Opens($data, $display_image = false);
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