<?php

namespace MailPoet\Logging;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\LogEntity;

/**
 * @extends Repository<LogEntity>
 */
class LogRepository extends Repository {
  protected function getEntityClassName() {
    return LogEntity::class;
  }

  /**
   * @param \DateTimeInterface|null $dateFrom
   * @param \DateTimeInterface|null $dateTo
   * @return LogEntity[]
   */
  public function getLogs(\DateTimeInterface $dateFrom = null, \DateTimeInterface $dateTo = null): array {
    $query = $this->doctrineRepository->createQueryBuilder('l')
      ->select('l');

    if ($dateFrom instanceof \DateTimeInterface) {
      $query
        ->where('l.createdAt > :dateFrom')
        ->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'));
    }
    if ($dateTo instanceof \DateTimeInterface) {
      $query
        ->andWhere('l.createdAt < :dateTo')
        ->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));
    }

    return $query->getQuery()->getResult();
  }
}
