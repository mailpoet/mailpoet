<?php declare(strict_types = 1);

namespace MailPoet\Logging;

use MailPoet\Entities\LogEntity;
use MailPoet\Listing\ListingRepository;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

class LogListingRepository extends ListingRepository {
  protected function applySelectClause(QueryBuilder $queryBuilder) {
    $queryBuilder->select('PARTIAL l.{id,name,level,message,createdAt}');
  }

  protected function applyFromClause(QueryBuilder $queryBuilder) {
    $queryBuilder->from(LogEntity::class, 'l');
  }

  protected function applyGroup(QueryBuilder $queryBuilder, string $group) {
    // Logs listing does not support groups.
  }

  private const SORTABLE_FIELDS = [
    'created_at' => 'createdAt',
    'name' => 'name',
    'id' => 'id',
  ];

  protected function applySorting(QueryBuilder $queryBuilder, string $sortBy, string $sortOrder) {
    // Whitelist the column to keep arbitrary input out of the DQL ORDER BY.
    $field = self::SORTABLE_FIELDS[$sortBy] ?? 'createdAt';
    $queryBuilder->addOrderBy("l.$field", $sortOrder);
    if ($field !== 'id') {
      $queryBuilder->addOrderBy('l.id', $sortOrder);
    }
  }

  protected function applySearch(QueryBuilder $queryBuilder, string $search, array $parameters) {
    $search = trim($search);
    if ($search === '') {
      return;
    }

    // LOCATE() keeps SQL wildcard characters literal for admin log searches.
    $queryBuilder
      ->andWhere('(LOCATE(:search, l.name) > 0 OR LOCATE(:search, l.message) > 0)')
      ->setParameter('search', $search);
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
    if (!empty($filters['name']) && is_array($filters['name'])) {
      $queryBuilder
        ->andWhere('l.name IN (:names)')
        ->setParameter('names', array_values($filters['name']));
    }
    if (!empty($filters['level']) && is_array($filters['level'])) {
      $levels = [];
      foreach ($filters['level'] as $level) {
        if (is_numeric($level)) {
          $levels[] = (int)$level;
        }
      }
      $queryBuilder
        ->andWhere('l.level IN (:levels)')
        ->setParameter('levels', $levels);
    }
  }

  protected function applyParameters(QueryBuilder $queryBuilder, array $parameters) {
    // Logs listing does not support additional parameters.
  }
}
