<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsClicks;
use MailPoet\Newsletter\Shortcodes\Categories\Link;

if(!defined('ABSPATH')) exit;

class Clicks {
  function track($data) {
    if(!$data || empty($data->link)) {
      return $this->abort();
    }
    $subscriber = $data->subscriber;
    $queue = $data->queue;
    $newsletter = $data->newsletter;
    $link = $data->link;
    $wp_user_preview = ($data->preview && $subscriber->isWPUser());
    // log statistics only if the action did not come from
    // a WP user previewing the newsletter
    if(!$wp_user_preview) {
      StatisticsClicks::createOrUpdateClickCount(
        $link->id,
        $subscriber->id,
        $newsletter->id,
        $queue->id
      );
      // track open event
      $open_event = new Opens();
      $open_event->track($data, $display_image = false);
    }
    $url = $this->processUrl($link->url, $newsletter, $subscriber, $queue, $wp_user_preview);
    $this->redirectToUrl($url);
  }

  function processUrl($url, $newsletter, $subscriber, $queue, $wp_user_preview) {
    if(preg_match('/\[link:(?P<action>.*?)\]/', $url, $shortcode)) {
      if(!$shortcode['action']) $this->abort();
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

  function abort() {
    status_header(404);
    exit;
  }

  function redirectToUrl($url) {
    header('Location: ' . $url, true, 302);
    exit;
  }
}