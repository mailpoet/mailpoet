<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class StatisticsClicks extends Model {
  public static $_table = MP_STATISTICS_CLICKS_TABLE;

  static function createOrUpdateClickCount($link_id, $subscriber_id, $newsletter_id, $queue_id) {
    $statistics = self::where('link_id', $link_id)
      ->where('subscriber_id', $subscriber_id)
      ->where('newsletter_id', $newsletter_id)
      ->where('queue_id', $queue_id)
      ->findOne();
    if(!$statistics) {
      $statistics = self::create();
      $statistics->link_id = $link_id;
      $statistics->subscriber_id = $subscriber_id;
      $statistics->newsletter_id = $newsletter_id;
      $statistics->queue_id = $queue_id;
      $statistics->count = 1;
    } else {
      $statistics->count++;
    }
    return $statistics->save();
  }
}
