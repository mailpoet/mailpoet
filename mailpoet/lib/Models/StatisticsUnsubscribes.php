<?php

namespace MailPoet\Models;

/**
 * @deprecated This model is deprecated. Use MailPoet\Statistics\StatisticsUnsubscribesRepository and respective Doctrine entities instead.
 * This class can be removed after 2022-03-25
 */

/**
 * @property int $newsletterId
 * @property int $subscriberId
 * @property int $queueId
 */
class StatisticsUnsubscribes extends Model {
  public static $_table = MP_STATISTICS_UNSUBSCRIBES_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public static function getOrCreate($subscriberId, $newsletterId, $queueId) {
    self::deprecationError(__FUNCTION__);
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

  /**
   * @deprecated This is here for displaying the deprecation warning for properties.
   */
  public function __get($key) {
    self::deprecationError('property "' . $key . '"');
    return parent::__get($key);
  }

  /**
   * @deprecated This is here for displaying the deprecation warning for static calls.
   */
  public static function __callStatic($name, $arguments) {
    self::deprecationError($name);
    return parent::__callStatic($name, $arguments);
  }

  private static function deprecationError($methodName) {
    trigger_error('Calling ' . $methodName . ' is deprecated and will be removed. Use MailPoet\Statistics\StatisticsUnsubscribesRepository and respective Doctrine entity instead.', E_USER_DEPRECATED);
  }
}
