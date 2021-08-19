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
