<?php

namespace MailPoet\Subscribers\ImportExport\Export;

use MailPoet\DI\ContainerWrapper;
use MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;

/**
 * Gets batches of subscribers from dynamic segments.
 */
class DynamicSubscribersGetter extends SubscribersGetter {

  protected $segmentIndex = 0;

  /** @var SingleSegmentLoader */
  private $dynamicSegmentsLoader;

  public function __construct($segmentsIds, $batchSize, SingleSegmentLoader $dynamicSegmentsLoader = null) {
    parent::__construct($segmentsIds, $batchSize);
    if ($dynamicSegmentsLoader === null) {
      $dynamicSegmentsLoader = ContainerWrapper::getInstance()->get(SingleSegmentLoader::class);
    }
    $this->dynamicSegmentsLoader = $dynamicSegmentsLoader;
  }

  public function reset() {
    parent::reset();
    $this->segmentIndex = 0;
  }

  protected function filter($subscribers) {
    $segmentId = $this->segmentsIds[$this->segmentIndex];

    $filters = $this->dynamicSegmentsLoader->load($segmentId)->getFilters();

    if (!is_array($filters) || empty($filters)) {
      return [];
    }

    $segment = Segment::findOne($segmentId);
    if (!$segment instanceof Segment) {
      return [];
    }
    $name = $segment->name;

    foreach ($filters as $filter) {
      $subscribers = $filter->toSql($subscribers);
    }

    return $subscribers
      ->selectMany([
        'list_status' => Subscriber::$_table . '.status',
      ])
      ->selectExpr("'" . $name . "' AS segment_name")
      ->offset($this->offset)
      ->limit($this->batchSize)
      ->findArray();
  }

  public function get() {
    if ($this->segmentIndex >= count($this->segmentsIds)) {
      $this->finished = true;
    }

    $subscribers = parent::get();

    if ($subscribers !== false && count($subscribers) < $this->batchSize) {
      $this->segmentIndex ++;
      $this->offset = 0;
      $this->finished = false;
    }

    return $subscribers;
  }
}
