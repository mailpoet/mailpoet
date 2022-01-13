<?php

namespace MailPoet\Models;

/**
 * @deprecated This model is deprecated. Use MailPoet\Statistics\StatisticsFormsRepository and respective Doctrine entities instead.
 * This class can be removed after 2021-10-02
 */

class StatisticsForms extends Model {
  public static $_table = MP_STATISTICS_FORMS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public static function getTotalSignups($formId = false) {
    self::deprecationError(__FUNCTION__);
    return self::where('form_id', $formId)->count();
  }

  public static function record($formId, $subscriberId) {
    self::deprecationError(__FUNCTION__);
    if ($formId > 0 && $subscriberId > 0) {
      // check if we already have a record for today
      $record = self::where('form_id', $formId)
        ->where('subscriber_id', $subscriberId)
        ->findOne();

      if ($record === false) {
        // create a new entry
        $record = self::create();
        $record->hydrate([
          'form_id' => $formId,
          'subscriber_id' => $subscriberId,
        ]);
        $record->save();
      }
      return $record;
    }
    return false;
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
    trigger_error('Calling ' . $methodName . ' is deprecated and will be removed. Use MailPoet\Statistics\StatisticsFormsRepository and respective Doctrine entities instead.', E_USER_DEPRECATED);
  }
}
