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

  public function getCountsPerType(): array {
    $results = $this->doctrineRepository->createQueryBuilder('s')
      ->select('s.type, COUNT(s) as cnt')
      ->where('s.deletedAt IS NULL')
      ->groupBy('s.type')
      ->getQuery()
      ->getResult();

    $countMap = [];
    foreach ($results as $result) {
      $countMap[$result['type']] = (int)$result['cnt'];
    }
    return $countMap;
  }
}
