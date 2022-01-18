<?php

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentListingRepository;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

class DynamicSegmentsListingRepository extends SegmentListingRepository {
  protected function applyParameters(QueryBuilder $queryBuilder, array $parameters) {
    $queryBuilder
      ->andWhere('s.type = :type')
      ->setParameter('type', SegmentEntity::TYPE_DYNAMIC);
  }
}
