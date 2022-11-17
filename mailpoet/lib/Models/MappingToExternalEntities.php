<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Models;

/**
 * @deprecated This model is deprecated and there is no replacement.
 * This class can be removed after 2023-01-06.
 */
class MappingToExternalEntities extends Model {
  public static $_table = MP_MAPPING_TO_EXTERNAL_ENTITIES_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

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

  public static function create($data = []) {
    self::deprecationError(__METHOD__);
    $relation = parent::create();
    $relation->hydrate($data);
    return $relation->save();
  }

  private static function deprecationError($methodName) {
    trigger_error(' Calling ' . esc_html($methodName) . ' is deprecated and will be removed. There is no replacement.', E_USER_DEPRECATED);
  }
}
