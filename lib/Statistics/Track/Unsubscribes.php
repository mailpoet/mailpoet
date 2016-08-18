<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsUnsubscribes;

if(!defined('ABSPATH')) exit;

class Unsubscribes {
  static function track($newsletter, $subscriber, $queue, $wp_user_preview) {
    if($wp_user_preview) return;
    StatisticsUnsubscribes::getOrCreate(
      $subscriber->id,
      $newsletter->id,
      $queue->id
    );
  }
}