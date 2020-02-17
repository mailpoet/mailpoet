<?php

namespace MailPoet\DynamicSegments\Persistence\Loading;

use MailPoet\DynamicSegments\Mappers\DBMapper;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\DynamicSegmentFilter;

class Loader {

  /** @var DBMapper */
  private $mapper;

  public function __construct(DBMapper $mapper) {
    $this->mapper = $mapper;
  }

  /**
   * @return DynamicSegment[]
   */
  public function load() {
    $segments = DynamicSegment::findAll();
    return $this->loadFilters($segments);
  }

  private function loadFilters(array $segments) {
    $ids = array_map(function($segment) {
      return $segment->id;
    }, $segments);
    $filters = DynamicSegmentFilter::getAllBySegmentIds($ids);

    return $this->mapper->mapSegments($segments, $filters);
  }
}
