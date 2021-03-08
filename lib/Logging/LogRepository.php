<?php

namespace MailPoet\Logging;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\LogEntity;
use MailPoet\Util\Helpers;

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
   * @param string|null $search
   * @param string $offset
   * @param string $limit
   * @return LogEntity[]
   */
  public function getLogs(
    \DateTimeInterface $dateFrom = null,
    \DateTimeInterface $dateTo = null,
    string $search = null,
    string $offset = null,
    string $limit = null
  ): array {
    $query = $this->doctrineRepository->createQueryBuilder('l')
      ->select('l');

    if ($dateFrom instanceof \DateTimeInterface) {
      $query
        ->andWhere('l.createdAt > :dateFrom')
        ->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'));
    }
    if ($dateTo instanceof \DateTimeInterface) {
      $query
        ->andWhere('l.createdAt < :dateTo')
        ->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));
    }
    if ($search) {
      $search = Helpers::escapeSearch($search);
      $query
        ->andWhere('l.name LIKE :search or l.message LIKE :search')
        ->setParameter('search', "%$search%");
    }

    $query->orderBy('l.createdAt', 'desc');
    if ($offset !== null) {
      $query->setFirstResult((int)$offset);
    }
    if ($limit === null) {
      $query->setMaxResults(500);
    } else {
      $query->setMaxResults((int)$limit);
    }


    return $query->getQuery()->getResult();
  }
}
