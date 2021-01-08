<?php

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentListingRepository;

class DynamicSegmentsListingRepository extends SegmentListingRepository {
  protected $types = [SegmentEntity::TYPE_DYNAMIC];
}
