<?php

namespace MailPoet\Models;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Models\Segment as MailPoetSegment;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @deprecated This model is deprecated. Use MailPoet\Segments\DynamicSegments\DynamicSegmentsListingRepository and respective Doctrine entities instead.
 * This class can be removed after 2021-09-25
 */

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $createdAt
 * @property string $updatedAt
 * @property string|null $deletedAt
 */
class DynamicSegment extends MailPoetSegment {

  const TYPE_DYNAMIC = SegmentEntity::TYPE_DYNAMIC;

  public function save() {
    self::deprecationError(__FUNCTION__);
    $this->set('type', DynamicSegment::TYPE_DYNAMIC);
    return parent::save();
  }

  public function dynamicSegmentFilters() {
    self::deprecationError(__FUNCTION__);
    return $this->has_many(__NAMESPACE__ . '\DynamicSegmentFilter', 'segment_id');
  }

  public static function findAll() {
    self::deprecationError(__FUNCTION__);
    $query = self::select('*');
    return $query->where('type', DynamicSegment::TYPE_DYNAMIC)
      ->whereNull('deleted_at')
      ->findMany();
  }

  public static function listingQuery(array $data = []) {
    self::deprecationError(__FUNCTION__);
    $query = self::select('*');
    $query->where('type', DynamicSegment::TYPE_DYNAMIC);
    if (isset($data['group'])) {
      $query->filter('groupBy', $data['group']);
    }
    if (isset($data['search'])) {
      $query->filter('search', $data['search']);
    }
    return $query;
  }

  public static function groups() {
    self::deprecationError(__FUNCTION__);
    return [
      [
        'name' => 'all',
        'label' => WPFunctions::get()->__('All', 'mailpoet'),
        'count' => DynamicSegment::getPublished()->where('type', DynamicSegment::TYPE_DYNAMIC)->count(),
      ],
      [
        'name' => 'trash',
        'label' => WPFunctions::get()->__('Trash', 'mailpoet'),
        'count' => parent::getTrashed()->where('type', DynamicSegment::TYPE_DYNAMIC)->count(),
      ],
    ];
  }

  public function delete() {
    self::deprecationError(__FUNCTION__);
    DynamicSegmentFilter::where('segment_id', $this->id)->deleteMany();
    return parent::delete();
  }

  public static function bulkTrash($orm) {
    self::deprecationError(__FUNCTION__);
    $count = parent::bulkAction($orm, function($ids) {
      $placeholders = join(',', array_fill(0, count($ids), '?'));
      DynamicSegment::rawExecute(join(' ', [
        'UPDATE `' . DynamicSegment::$_table . '`',
        'SET `deleted_at` = NOW()',
        'WHERE `id` IN (' . $placeholders . ')',
      ]), $ids);
    });

    return ['count' => $count];
  }

  public static function bulkDelete($orm) {
    self::deprecationError(__FUNCTION__);
    $count = parent::bulkAction($orm, function($ids) {
      $placeholders = join(',', array_fill(0, count($ids), '?'));
      DynamicSegmentFilter::rawExecute(join(' ', [
        'DELETE FROM `' . DynamicSegmentFilter::$_table . '`',
        'WHERE `segment_id` IN (' . $placeholders . ')',
      ]), $ids);
      DynamicSegment::rawExecute(join(' ', [
        'DELETE FROM `' . DynamicSegment::$_table . '`',
        'WHERE `id` IN (' . $placeholders . ')',
      ]), $ids);
    });

    return ['count' => $count];
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
    trigger_error('Calling ' . $methodName . ' is deprecated and will be removed. Use MailPoet\Segments\DynamicSegments\DynamicSegmentsListingRepository and respective Doctrine entities instead.', E_USER_DEPRECATED);
  }
}
