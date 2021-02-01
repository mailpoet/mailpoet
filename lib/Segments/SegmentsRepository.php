<?php

namespace MailPoet\Segments;

use Carbon\Carbon;
use DateTime;
use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\NotFoundException;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\EntityManager;

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

  public function bulkDelete(array $ids) {
    if (empty($ids)) {
      return 0;
    }

    return $this->entityManager->transactional(function (EntityManager $entityManager) use ($ids) {
      $subscriberSegmentTable = $entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
      $segmentTable = $entityManager->getClassMetadata(SegmentEntity::class)->getTableName();

      $entityManager->getConnection()->executeUpdate("
         DELETE ss FROM $subscriberSegmentTable ss
         JOIN $segmentTable s ON ss.`segment_id` = s.`id`
         WHERE ss.`segment_id` IN (:ids)
         AND s.`type` = :typeDefault
      ", [
        'ids' => $ids,
        'typeDefault' => SegmentEntity::TYPE_DEFAULT,
      ], ['ids' => Connection::PARAM_INT_ARRAY]);

      return $entityManager->getConnection()->executeUpdate("
         DELETE s FROM $segmentTable s
         WHERE s.`id` IN (:ids)
         AND s.`type` = :typeDefault
      ", [
        'ids' => $ids,
        'typeDefault' => SegmentEntity::TYPE_DEFAULT,
      ], ['ids' => Connection::PARAM_INT_ARRAY]);
    });
  }

  public function bulkTrash(array $ids): int {
    return $this->updateDeletedAt($ids, new Carbon());
  }

  public function bulkRestore(array $ids): int {
    return $this->updateDeletedAt($ids, null);
  }

  private function updateDeletedAt(array $ids, ?DateTime $deletedAt): int {
    if (empty($ids)) {
      return 0;
    }

    $rows = $this->entityManager->createQueryBuilder()->update(SegmentEntity::class, 's')
    ->set('s.deletedAt', ':deletedAt')
    ->where('s.id IN (:ids)')
    ->andWhere('s.type IN (:types)')
    ->setParameter('deletedAt', $deletedAt)
    ->setParameter('ids', $ids)
    ->setParameter('types', [SegmentEntity::TYPE_DEFAULT, SegmentEntity::TYPE_WP_USERS])
    ->getQuery()->execute();

    return $rows;
  }
}
