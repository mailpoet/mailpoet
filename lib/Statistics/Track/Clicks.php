<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\NewsletterLink;
use MailPoet\Models\StatisticsClicks;

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
    $link = NewsletterLink::where('hash', $hash)->findOne();
    if(!$link) $this->abort();
    $statistics = StatisticsClicks::where('link_id', $link->id)
      ->where('subscriber_id', $subscriber_id)
      ->where('newsletter_id', $newsletter_id)
      ->where('queue_id', $queue_id)
      ->findOne();
    if(!$statistics) {
      $statistics = StatisticsClicks::create();
      $statistics->newsletter_id = $newsletter_id;
      $statistics->link_id = $link->id;
      $statistics->subscriber_id = $subscriber_id;
      $statistics->queue_id = $queue_id;
      $statistics->count = 1;
      $statistics->save();
    } else {
      $statistics->count = (int) $statistics->count++;
      $statistics->save();
    }
    header('Location: ' . $link->url, true, 301);
  }

  private function abort() {
    header('HTTP/1.0 404 Not Found');
    exit;
  }
}