<?php

namespace MailPoet\Segments;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SegmentEntity;

/**
 * @extends Repository<SegmentEntity>
 */
class SegmentsRepository extends Repository {
  protected function getEntityClassName() {
    return SegmentEntity::class;
  }
}
