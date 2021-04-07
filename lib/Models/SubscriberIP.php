<?php

namespace MailPoet\Models;

/**
 * @deprecated This model is deprecated. Use MailPoet\Subscribers\SubscriberIPsRepository and respective Doctrine entities instead.
 * This class can be removed after 2021-10-07
 * @property string $ip
 */
class SubscriberIP extends Model {
  public static $_table = MP_SUBSCRIBER_IPS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

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
    trigger_error('Calling ' . $methodName . ' is deprecated and will be removed. Use MailPoet\Subscribers\SubscriberIPsRepository and respective Doctrine entities instead.', E_USER_DEPRECATED);
  }
}
