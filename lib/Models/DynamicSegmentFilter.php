<?php

namespace MailPoet\Models;

use MailPoet\Models\Model;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @property array|string|null $filter_data
 * @property string $segment_id
 */
class DynamicSegmentFilter extends Model {

  public static $_table = MP_DYNAMIC_SEGMENTS_FILTERS_TABLE;

  function save() {
    if (is_null($this->filter_data)) {
      $this->filter_data = [];
    }

    if (!WPFunctions::get()->isSerialized($this->filter_data)) {
      $this->filter_data = serialize($this->filter_data);
    }

    return parent::save();
  }

  static function getAllBySegmentIds($segmentIds) {
    if (empty($segmentIds)) return [];
    $query = self::tableAlias('filters')
      ->whereIn('filters.segment_id', $segmentIds);

    $query->findMany();
    return $query->findMany();
  }

  public function __get($name) {
    $value = parent::__get($name);
    if ($name === 'filter_data' && WPFunctions::get()->isSerialized($value)) {
      return unserialize($value);
    }
    return $value;
  }

  static function deleteAllBySegmentIds($segmentIds) {
    if (empty($segmentIds)) return;

    $query = self::tableAlias('filters')
      ->whereIn('segment_id', $segmentIds);

    $query->deleteMany();

  }

}
