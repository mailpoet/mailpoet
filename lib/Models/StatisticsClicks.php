<?php

namespace MailPoet\Models;

/**
 * @property int $newsletterId
 * @property int $subscriberId
 * @property int $queueId
 * @property int $linkId
 * @property int $count
 */
class StatisticsClicks extends Model {
  public static $_table = MP_STATISTICS_CLICKS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration
}
