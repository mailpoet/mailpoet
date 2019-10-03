<?php

namespace MailPoet\Premium\DynamicSegments\Persistence\Loading;

use MailPoet\Premium\DynamicSegments\Mappers\DBMapper;
use MailPoet\Premium\Models\DynamicSegment;
use MailPoet\Premium\Models\DynamicSegmentFilter;

class Loader {

  /** @var DBMapper */
  private $mapper;

  public function __construct(DBMapper $mapper) {
    $this->mapper = $mapper;
  }

  /**
   * @return DynamicSegment[]
   */
  function load() {
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