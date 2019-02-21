<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

/**
 * @property int $newsletter_id
 * @property int $subscriber_id
 * @property int $queue_id
 * @property int $link_id
 * @property int $count
 */
class StatisticsClicks extends Model {
  public static $_table = MP_STATISTICS_CLICKS_TABLE;

  static function createOrUpdateClickCount($link_id, $subscriber_id, $newsletter_id, $queue_id) {
    $statistics = self::where('link_id', $link_id)
      ->where('subscriber_id', $subscriber_id)
      ->where('newsletter_id', $newsletter_id)
      ->where('queue_id', $queue_id)
      ->findOne();
    if (!$statistics) {
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

  static function getAllForSubscriber(Subscriber $subscriber) {
    return static::tableAlias('clicks')
      ->select('clicks.id', 'id')
      ->select('newsletter_rendered_subject')
      ->select('clicks.created_at', 'created_at')
      ->select('url')
      ->join(
       SendingQueue::$_table,
       array('clicks.queue_id', '=', 'queue.id'),
       'queue'
      )
      ->join(
        NewsletterLink::$_table,
        array('clicks.link_id', '=', 'link.id'),
        'link'
      )
      ->where('clicks.subscriber_id', $subscriber->id())
      ->orderByAsc('url');
  }
}
