<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsClicks;
use MailPoet\Newsletter\Shortcodes\Categories\Link;

if(!defined('ABSPATH')) exit;

class Clicks {
  static function track($data) {
    if(!$data || empty($data->link)) self::abort();
    $subscriber = $data->subscriber;
    $queue = $data->queue;
    $newsletter = $data->newsletter;
    $link = $data->link;
    $wp_user_preview = ($data->preview && $subscriber->isWPUser());
    // log statistics only if the action did not come from
    // a WP user previewing the newsletter
    if(!$wp_user_preview) {
      $statistics = StatisticsClicks::createOrUpdateClickCount(
        $link->id,
        $subscriber->id,
        $newsletter->id,
        $queue->id
      );
      // track open event
      Opens::track($data, $display_image = false);
    }
    $url = self::processUrl($link->url, $newsletter, $subscriber, $queue, $wp_user_preview);
    self::redirectToUrl($url);
  }

  static function processUrl($url, $newsletter, $subscriber, $queue, $wp_user_preview) {
    if(preg_match('/\[link:(?P<action>.*?)\]/', $url, $shortcode)) {
      if(!$shortcode['action']) self::abort();
      $url = Link::processShortcodeAction(
        $shortcode['action'],
        $newsletter,
        $subscriber,
        $queue,
        $wp_user_preview
      );
    }
    return $url;
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