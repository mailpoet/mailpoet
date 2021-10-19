<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * @extends Repository<DynamicSegmentFilterEntity>
 */
class DynamicSegmentFilterRepository extends Repository {
  public function __construct(
    EntityManager $entityManager
  ) {
    parent::__construct($entityManager);
  }

  protected function getEntityClassName() {
    return DynamicSegmentFilterEntity::class;
  }

  public function findOnyBySegmentTypeAndAction(string $segmentType, string $action): ?DynamicSegmentFilterEntity {
    $segmentTypeLength = strlen($segmentType);
    $actionLength = strlen($action);
    return $this->entityManager->createQueryBuilder()
      ->select('dsf')
      ->from(DynamicSegmentFilterEntity::class, 'dsf')
      ->where('dsf.filterData.filterData LIKE :segmentType')
      ->andWhere('dsf.filterData.filterData LIKE :action')
      ->setParameter('segmentType', "%s:11:\"segmentType\";s:{$segmentTypeLength}:\"{$segmentType}\"%")
      ->setParameter('action', "%s:6:\"action\";s:{$actionLength}:\"{$action}\"%")
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();
  }
}
