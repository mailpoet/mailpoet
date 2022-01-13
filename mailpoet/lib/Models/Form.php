<?php

namespace MailPoet\Models;

/**
 * @deprecated This model is deprecated. Use \MailPoet\Form\FormsRepository and respective Doctrine entities instead.
 * This class can be removed after 2022-04-15
 */
class Form extends Model {
  public static $_table = MP_FORMS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

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
    trigger_error('Calling ' . $methodName . ' is deprecated and will be removed. Use MailPoet\Statistics\StatisticsFormsRepository and respective Doctrine entities instead.', E_USER_DEPRECATED);
  }
}
