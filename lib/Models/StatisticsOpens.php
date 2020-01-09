<?php

namespace MailPoet\Models;

/**
 * @property int $newsletter_id
 * @property int $subscriber_id
 * @property int $queue_id
 */
class StatisticsOpens extends Model {
  public static $_table = MP_STATISTICS_OPENS_TABLE;

  public static function getOrCreate($subscriberId, $newsletterId, $queueId) {
    $statistics = self::where('subscriber_id', $subscriberId)
      ->where('newsletter_id', $newsletterId)
      ->where('queue_id', $queueId)
      ->findOne();
    if (!$statistics) {
      $statistics = self::create();
      $statistics->subscriberId = $subscriberId;
      $statistics->newsletterId = $newsletterId;
      $statistics->queueId = $queueId;
      $statistics->save();
    }
    return $statistics;
  }
}
