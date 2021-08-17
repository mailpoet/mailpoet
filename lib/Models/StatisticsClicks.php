<?php

namespace MailPoet\Models;

use DateTimeInterface;

/**
 * @property int $newsletterId
 * @property int $subscriberId
 * @property int $queueId
 * @property int $linkId
 * @property int $count
 */
class StatisticsClicks extends Model {
  public static $_table = MP_STATISTICS_CLICKS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public static function getAllForSubscriber(Subscriber $subscriber) {
    return static::tableAlias('clicks')
      ->select('clicks.id', 'id')
      ->select('newsletter_rendered_subject')
      ->select('clicks.created_at', 'created_at')
      ->select('url')
      ->join(
       SendingQueue::$_table,
       ['clicks.queue_id', '=', 'queue.id'],
       'queue'
      )
      ->join(
        NewsletterLink::$_table,
        ['clicks.link_id', '=', 'link.id'],
        'link'
      )
      ->where('clicks.subscriber_id', $subscriber->id())
      ->orderByAsc('url');
  }

  public static function findLatestPerNewsletterBySubscriber(Subscriber $subscriber, DateTimeInterface $from, DateTimeInterface $to) {
    // subquery to find latest click IDs for each newsletter
    $table = self::$_table;
    $latestClickIdsPerNewsletterQuery = "
      SELECT MAX(id)
      FROM $table
      WHERE subscriber_id = :subscriber_id
      AND updated_at > :from
      AND updated_at < :to
      GROUP BY newsletter_id
    ";

    return static::tableAlias('clicks')
      ->whereRaw("clicks.id IN ($latestClickIdsPerNewsletterQuery)", [
        'subscriber_id' => $subscriber->id,
        'from' => $from->format('Y-m-d H:i:s'),
        'to' => $to->format('Y-m-d H:i:s'),
      ])
      ->findMany();
  }
}
