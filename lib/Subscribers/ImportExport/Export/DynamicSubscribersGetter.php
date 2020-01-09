<?php

namespace MailPoet\Subscribers\ImportExport\Export;

use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Gets batches of subscribers from dynamic segments.
 */
class DynamicSubscribersGetter extends SubscribersGetter {

  protected $segment_index = 0;

  /** @var WPFunctions */
  private $wp;

  public function __construct($segmentsIds, $batchSize, WPFunctions $wp = null) {
    parent::__construct($segmentsIds, $batchSize);
    if ($wp == null) {
      $wp = new WPFunctions;
    }
    $this->wp = $wp;
  }

  public function reset() {
    parent::reset();
    $this->segmentIndex = 0;
  }

  protected function filter($subscribers) {
    $segmentId = $this->segmentsIds[$this->segmentIndex];

    $filters = $this->wp->applyFilters(
      'mailpoet_get_segment_filters',
      $segmentId
    );

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
