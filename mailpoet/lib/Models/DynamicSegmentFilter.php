<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Models;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @property array|string|null $filterData
 * @property string $segmentId
 * @deprecated This model is deprecated. Use \MailPoet\Segments\DynamicSegments\DynamicSegmentFilterRepository and
 * \MailPoet\Entities\DynamicSegmentFilterEntity.
 * This class can be removed after 2022-11-04.
 */
class DynamicSegmentFilter extends Model {

  public static $_table = MP_DYNAMIC_SEGMENTS_FILTERS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public function save() {
    self::deprecationError(__METHOD__);
    if (is_null($this->filterData)) {
      $this->filterData = [];
    }

    if (!WPFunctions::get()->isSerialized($this->filterData)) {
      $this->filterData = serialize($this->filterData);
    }

    return parent::save();
  }

  public static function getAllBySegmentIds($segmentIds) {
    self::deprecationError(__METHOD__);
    if (empty($segmentIds)) return [];
    $query = self::tableAlias('filters')
      ->whereIn('filters.segment_id', $segmentIds);

    $query->findMany();
    return $query->findMany();
  }

  public function __get($name) {
    self::deprecationError($name);
    $name = Helpers::camelCaseToUnderscore($name);
    $value = parent::__get($name);
    if ($name === 'filter_data' && $value !== null && WPFunctions::get()->isSerialized($value)) {
      return unserialize($value);
    }
    return $value;
  }

  /**
   * @deprecated This is here for displaying the deprecation warning for static calls.
   */
  public static function __callStatic($name, $arguments) {
    self::deprecationError($name);
    return parent::__callStatic($name, $arguments);
  }

  public static function deleteAllBySegmentIds($segmentIds) {
    self::deprecationError(__METHOD__);
    if (empty($segmentIds)) return;

    $query = self::tableAlias('filters')
      ->whereIn('segment_id', $segmentIds);

    $query->deleteMany();

  }

  private static function deprecationError($methodName) {
    trigger_error(' Calling ' . esc_html($methodName) . ' is deprecated and will be removed. Use \MailPoet\Segments\DynamicSegments\DynamicSegmentFilterRepository and respective Doctrine entities instead.', E_USER_DEPRECATED);
  }
}
