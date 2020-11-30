<?php

namespace MailPoet\Models;

use MailPoet\Models\Model;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @property array|string|null $filterData
 * @property string $segmentId
 */
class DynamicSegmentFilter extends Model {

  public static $_table = MP_DYNAMIC_SEGMENTS_FILTERS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public function save() {
    if (is_null($this->filterData)) {
      $this->filterData = [];
    }

    if (!WPFunctions::get()->isSerialized($this->filterData)) {
      $this->filterData = serialize($this->filterData);
    }

    return parent::save();
  }

  public static function getAllBySegmentIds($segmentIds) {
    if (empty($segmentIds)) return [];
    $query = self::tableAlias('filters')
      ->whereIn('filters.segment_id', $segmentIds);

    $query->findMany();
    return $query->findMany();
  }

  public function __get($name) {
    $name = Helpers::camelCaseToUnderscore($name);
    $value = parent::__get($name);
    if ($name === 'filter_data' && $value !== null && WPFunctions::get()->isSerialized($value)) {
      return unserialize($value);
    }
    return $value;
  }

  public static function deleteAllBySegmentIds($segmentIds) {
    if (empty($segmentIds)) return;

    $query = self::tableAlias('filters')
      ->whereIn('segment_id', $segmentIds);

    $query->deleteMany();

  }
}
