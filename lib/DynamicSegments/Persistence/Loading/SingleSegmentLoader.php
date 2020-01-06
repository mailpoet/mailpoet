<?php

namespace MailPoet\DynamicSegments\Persistence\Loading;

use MailPoet\DynamicSegments\Mappers\DBMapper;
use MailPoet\Models\DynamicSegment;

class SingleSegmentLoader {

  /** @var DBMapper */
  private $mapper;

  public function __construct(DBMapper $mapper) {
    $this->mapper = $mapper;
  }

  /**
   * @param string|int $segmentId
   * @return DynamicSegment
   */
  public function load($segmentId) {

    $segment = DynamicSegment::findOne($segmentId);
    if (!$segment instanceof DynamicSegment) {
      throw new \InvalidArgumentException('Segment not found');
    }

    $filters = $segment->dynamicSegmentFilters()->findMany();

    return $this->mapper->mapSegment($segment, $filters);
  }



}
