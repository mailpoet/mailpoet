<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Models;

/**
 * @property int $newsletterId
 * @property int $subscriberId
 * @property int $queueId
 * @property int $linkId
 * @property int $count
 *
 * @deprecated This model is deprecated. Use \MailPoet\Statistics\StatisticsClicksRepository and
 * \MailPoet\Entities\StatisticsClickEntity
 * This class can be removed after 2022-11-04.
 */
class StatisticsClicks extends Model {
  public static $_table = MP_STATISTICS_CLICKS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

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
    trigger_error(
      'Calling ' . esc_html($methodName) . ' is deprecated and will be removed. Use \MailPoet\Statistics\StatisticsClicksRepository and \MailPoet\Entities\StatisticsClickEntity.',
      E_USER_DEPRECATED
    );
  }
}
