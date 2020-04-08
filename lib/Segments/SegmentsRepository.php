<?php

namespace MailPoet\Segments;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SegmentEntity;

/**
 * @method SegmentEntity[] findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null)
 * @method SegmentEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method SegmentEntity|null findOneById(mixed $id)
 * @method void persist(SegmentEntity $entity)
 * @method void remove(SegmentEntity $entity)
 */
class SegmentsRepository extends Repository {
  protected function getEntityClassName() {
    return SegmentEntity::class;
  }
}
