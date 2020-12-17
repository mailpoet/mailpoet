<?php

namespace MailPoet\Segments;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SegmentEntity;
use MailPoet\NotFoundException;

/**
 * @extends Repository<SegmentEntity>
 */
class SegmentsRepository extends Repository {
  protected function getEntityClassName() {
    return SegmentEntity::class;
  }

  public function getWPUsersSegment() {
    return $this->findOneBy(['type' => SegmentEntity::TYPE_WP_USERS]);
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

  public function isNameUnique(string $name, ?int $id): bool {
    $qb = $this->doctrineRepository->createQueryBuilder('s')
      ->select('s')
      ->where('s.name = :name')
      ->setParameter('name', $name);

    if ($id !== null) {
      $qb->andWhere('s.id != :id')
        ->setParameter('id', $id);
    }

    $results = $qb->getQuery()
      ->getResult();

    return count($results) === 0;
  }

  public function createOrUpdate(
    string $name,
    string $description = '',
    ?int $id = null
  ): SegmentEntity {
    if ($id) {
      $segment = $this->findOneById($id);
      if (!$segment instanceof SegmentEntity) {
        throw new NotFoundException("Segment with ID [{$id}] was not found.");
      }
      $segment->setName($name);
      $segment->setDescription($description);
    } else {
      $segment = new SegmentEntity($name, SegmentEntity::TYPE_DEFAULT, $description);
      $this->persist($segment);
    }
    $this->flush();
    return $segment;
  }
}
