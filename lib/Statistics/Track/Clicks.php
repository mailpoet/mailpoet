<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsClicks;
use MailPoet\Newsletter\Shortcodes\Categories\Link;

if(!defined('ABSPATH')) exit;

class Clicks {
  static function track($data) {
    if(!$data || empty($data['link'])) return false;
    $subscriber = $data['subscriber'];
    $queue = $data['queue'];
    $newsletter = $data['newsletter'];
    $link = $data['link'];
    // log statistics only if the action did not come from
    // an admin user previewing the newsletter
    if(!$data['preview'] && !$subscriber['wp_user_id']) {
      $statistics = StatisticsClicks::where('link_id', $link['id'])
        ->where('subscriber_id', $subscriber['id'])
        ->where('newsletter_id', $newsletter['id'])
        ->where('queue_id', $queue['id'])
      ->findOne();
      if(!$statistics) {
        // track open event in case it did not register
        self::trackOpenEvent($data);
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
    }
    $url = self::processUrl($link['url'], $newsletter, $subscriber, $queue);
    self::redirectToUrl($url);
  }

  static function processUrl($url, $newsletter, $subscriber, $queue) {
    if(preg_match('/\[link:(?P<action>.*?)\]/', $url, $shortcode)) {
      if(!$shortcode['action']) self::abort();
      $url = Link::processShortcodeAction(
        $shortcode['action'],
        $newsletter,
        $subscriber,
        $queue
      );
    }
    return $url;
  }

  static function trackOpenEvent($data) {
    return Opens::track($data, $display_image = false);
  }

  static function abort() {
    status_header(404);
    exit;
  }

  static function redirectToUrl($url) {
    header('Location: ' . $url, true, 302);
    exit;
  }
}