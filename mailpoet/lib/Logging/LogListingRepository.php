<?php declare(strict_types = 1);

namespace MailPoet\Logging;

use MailPoet\Entities\LogEntity;
use MailPoet\Listing\ListingRepository;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

class LogListingRepository extends ListingRepository {
  protected function applySelectClause(QueryBuilder $queryBuilder) {
    $queryBuilder->select('PARTIAL l.{id,name,message,createdAt}');
  }

  protected function applyFromClause(QueryBuilder $queryBuilder) {
    $queryBuilder->from(LogEntity::class, 'l');
  }

  protected function applyGroup(QueryBuilder $queryBuilder, string $group) {
    // Logs listing does not support groups.
  }

  protected function applySorting(QueryBuilder $queryBuilder, string $sortBy, string $sortOrder) {
    $queryBuilder->addOrderBy("l.$sortBy", $sortOrder);
    if ($sortBy !== 'id') {
      $queryBuilder->addOrderBy('l.id', $sortOrder);
    }
  }

  protected function applySearch(QueryBuilder $queryBuilder, string $search, array $parameters) {
    $queryBuilder
      ->andWhere('LOCATE(:search, l.name) > 0 or LOCATE(:search, l.message) > 0')
      ->setParameter('search', trim($search));
  }

  protected function applyFilters(QueryBuilder $queryBuilder, array $filters) {
    if (!empty($filters['from'])) {
      $queryBuilder
        ->andWhere('l.createdAt >= :dateFrom')
        ->setParameter('dateFrom', $filters['from'] . ' 00:00:00');
    }
    if (!empty($filters['to'])) {
      $queryBuilder
        ->andWhere('l.createdAt <= :dateTo')
        ->setParameter('dateTo', $filters['to'] . ' 23:59:59');
    }
  }

  protected function applyParameters(QueryBuilder $queryBuilder, array $parameters) {
    // Logs listing does not support additional parameters.
  }
}
