<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Logging;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\LogEntity;
use MailPoet\Util\Helpers;
use MailPoetVendor\Carbon\Carbon;

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
        ->andWhere('l.createdAt >= :dateFrom')
        ->setParameter('dateFrom', $dateFrom->format('Y-m-d 00:00:00'));
    }
    if ($dateTo instanceof \DateTimeInterface) {
      $query
        ->andWhere('l.createdAt <= :dateTo')
        ->setParameter('dateTo', $dateTo->format('Y-m-d 23:59:59'));
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

  public function purgeOldLogs(int $daysToKeepLogs) {
    $queryBuilder = $this->entityManager->createQueryBuilder();
    return $queryBuilder->delete(LogEntity::class, 'l')
      ->where('l.createdAt < :days')
      ->setParameter('days', Carbon::now()->subDays($daysToKeepLogs)->toDateTimeString())
      ->getQuery()->execute();
  }
}
