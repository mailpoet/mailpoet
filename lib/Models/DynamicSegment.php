<?php

namespace MailPoet\Models;

use MailPoet\DynamicSegments\Filters\Filter;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Models\Segment as MailPoetSegment;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $createdAt
 * @property string $updatedAt
 */
class DynamicSegment extends MailPoetSegment {

  const TYPE_DYNAMIC = SegmentEntity::TYPE_DYNAMIC;

  /** @var Filter[] */
  private $filters = [];

  /**
   * @return Filter[]
   */
  public function getFilters() {
    return $this->filters;
  }

  /**
   * @param Filter[] $filters
   */
  public function setFilters(array $filters) {
    $this->filters = $filters;
  }

  public function save() {
    $this->set('type', DynamicSegment::TYPE_DYNAMIC);
    return parent::save();
  }

  public function dynamicSegmentFilters() {
    return $this->has_many(__NAMESPACE__ . '\DynamicSegmentFilter', 'segment_id');
  }

  public static function findAll() {
    $query = self::select('*');
    return $query->where('type', DynamicSegment::TYPE_DYNAMIC)
      ->whereNull('deleted_at')
      ->findMany();
  }

  public static function listingQuery(array $data = []) {
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
    DynamicSegmentFilter::where('segment_id', $this->id)->deleteMany();
    return parent::delete();
  }

  public static function bulkTrash($orm) {
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
}
