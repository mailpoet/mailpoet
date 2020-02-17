<?php

namespace MailPoet\Models;

/**
 * @property int $newsletterId
 * @property int $subscriberId
 * @property int $queueId
 */
class StatisticsOpens extends Model {
  public static $_table = MP_STATISTICS_OPENS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

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
