<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Shortcodes\Categories\Link;

if(!defined('ABSPATH')) exit;

class Clicks {
  public $data;

  function __construct($data) {
    $this->data = $data;
  }

  function track($data = false) {
    $data = ($data) ? $data : $this->data;
    $newsletter = $this->getNewsletter($data['newsletter']);
    $queue = $this->getQueue($data['queue']);
    // verify if queue belongs to the newsletter
    if($newsletter && $queue) {
      $queue = ($queue['newsletter_id'] === $newsletter['id']) ?
        $queue :
        false;
    }
    $subscriber = $this->getSubscriber($data['subscriber']);
    // verify if subscriber belongs to the queue
    if($queue && $subscriber) {
      // check if this newsletter was sent to
      $subscriber = (in_array($subscriber['id'], $queue['subscribers']['processed'])) ?
        $subscriber :
        false;
    }
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
      $this->trackOpenEvent($data);
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
    $this->redirectToUrl($url);
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
    }
    return $url;
  }

  function trackOpenEvent($data) {
    $open = new Opens($data, $display_image = false);
    return $open->track();
  }

  function abort() {
    status_header(404);
    exit;
  }

  function redirectToUrl($url) {
    header('Location: ' . $url, true, 302);
    exit;
  }
}