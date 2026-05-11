<?php declare(strict_types = 1);

namespace MailPoet\Listing;

use MailPoet\Util\Helpers;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

abstract class ListingRepository {
  public const ACTIONABLE_IDS_BATCH_SIZE = 500;

  /** @var QueryBuilder */
  protected $queryBuilder;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->queryBuilder = $entityManager->createQueryBuilder();
  }

  public function getData(ListingDefinition $definition): array {
    $queryBuilder = clone $this->queryBuilder;
    $sortBy = Helpers::underscoreToCamelCase($definition->getSortBy());
    $this->applySelectClause($queryBuilder);
    $this->applyFromClause($queryBuilder);
    $this->applyConstraints($queryBuilder, $definition);
    $this->applySorting($queryBuilder, $sortBy, $definition->getSortOrder());
    $this->applyPaging($queryBuilder, $definition->getOffset(), $definition->getLimit());
    return $queryBuilder->getQuery()->getResult();
  }

  public function getCount(ListingDefinition $definition): int {
    $queryBuilder = clone $this->queryBuilder;
    $this->applyFromClause($queryBuilder);
    $this->applyConstraints($queryBuilder, $definition);
    $alias = $queryBuilder->getRootAliases()[0];
    $queryBuilder->select("COUNT(DISTINCT $alias)");
    return (int)$queryBuilder->getQuery()->getSingleScalarResult();
  }

  public function getActionableIds(ListingDefinition $definition): array {
    $ids = [];
    $this->iterateActionableIds($definition, function(array $batchIds) use (&$ids): void {
      $ids = array_merge($ids, $batchIds);
    });
    return $ids;
  }

  public function iterateActionableIds(ListingDefinition $definition, callable $callback, int $batchSize = self::ACTIONABLE_IDS_BATCH_SIZE): void {
    $batchSize = max(1, $batchSize);
    $selectedIds = $definition->getSelection();
    if (!empty($selectedIds)) {
      foreach (array_chunk($selectedIds, $batchSize) as $batchIds) {
        $callback(array_map('intval', $batchIds));
      }
      return;
    }

    $queryBuilder = clone $this->queryBuilder;
    $this->applyFromClause($queryBuilder);
    $this->applyConstraints($queryBuilder, $definition);
    $alias = $queryBuilder->getRootAliases()[0];

    $maxIdQueryBuilder = clone $queryBuilder;
    $maxIdQueryBuilder->select("MAX($alias.id)");
    $maxId = (int)$maxIdQueryBuilder->getQuery()->getSingleScalarResult();
    if ($maxId <= 0) {
      return;
    }

    $lastId = 0;
    while ($lastId < $maxId) {
      $batchQueryBuilder = clone $queryBuilder;
      $batchQueryBuilder
        ->select("DISTINCT $alias.id")
        ->andWhere("$alias.id > :lastActionableId")
        ->andWhere("$alias.id <= :maxActionableId")
        ->setParameter('lastActionableId', $lastId)
        ->setParameter('maxActionableId', $maxId)
        ->orderBy("$alias.id", 'ASC')
        ->setMaxResults($batchSize);

      $ids = array_map('intval', array_column($batchQueryBuilder->getQuery()->getScalarResult(), 'id'));
      if (empty($ids)) {
        return;
      }

      $callback($ids);
      $lastId = max($ids);
    }
  }

  public function getGroups(ListingDefinition $definition): array {
    return [];
  }

  public function getFilters(ListingDefinition $definition): array {
    return [];
  }

  abstract protected function applySelectClause(QueryBuilder $queryBuilder);

  abstract protected function applyFromClause(QueryBuilder $queryBuilder);

  protected function applyConstraints(QueryBuilder $queryBuilder, ListingDefinition $definition) {
    $group = $definition->getGroup();
    if ($group) {
      $this->applyGroup($queryBuilder, $group);
    }

    $search = $definition->getSearch();
    $parameters = $definition->getParameters();

    if ($search && strlen(trim($search)) > 0) {
      $this->applySearch($queryBuilder, $search, $parameters ?: []);
    }

    $filters = $definition->getFilters();
    if ($filters) {
      $this->applyFilters($queryBuilder, $filters);
    }

    if ($parameters) {
      $this->applyParameters($queryBuilder, $parameters);
    }
  }

  abstract protected function applyGroup(QueryBuilder $queryBuilder, string $group);

  abstract protected function applySearch(QueryBuilder $queryBuilder, string $search, array $parameters);

  abstract protected function applyFilters(QueryBuilder $queryBuilder, array $filters);

  abstract protected function applyParameters(QueryBuilder $queryBuilder, array $parameters);

  protected function applySorting(QueryBuilder $queryBuilder, string $sortBy, string $sortOrder) {
    $alias = $this->queryBuilder->getRootAliases()[0];
    $queryBuilder->addOrderBy("$alias.$sortBy", $sortOrder);
  }

  protected function applyPaging(QueryBuilder $queryBuilder, int $offset, int $limit) {
    $queryBuilder->setFirstResult($offset);
    $queryBuilder->setMaxResults($limit);
  }
}
