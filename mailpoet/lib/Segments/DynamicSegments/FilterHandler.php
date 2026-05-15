<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\SegmentDependencyValidator;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class FilterHandler {
  /** @var EntityManager */
  private $entityManager;

  /** @var SegmentDependencyValidator */
  private $segmentDependencyValidator;

  /** @var FilterFactory */
  private $filterFactory;

  public function __construct(
    EntityManager $entityManager,
    SegmentDependencyValidator $segmentDependencyValidator,
    FilterFactory $filterFactory
  ) {

    $this->entityManager = $entityManager;
    $this->segmentDependencyValidator = $segmentDependencyValidator;
    $this->filterFactory = $filterFactory;
  }

  public function apply(QueryBuilder $queryBuilder, SegmentEntity $segment): QueryBuilder {
    $allFilters = $segment->getDynamicFilters();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $pluginsForAllFiltersMissing = $this->segmentDependencyValidator->getMissingPluginsByAllFilters($allFilters);
    if ($pluginsForAllFiltersMissing) {
      return $queryBuilder->andWhere('1 = 0');
    }

    $groups = $segment->getFilterGroups();
    $builtGroups = [];
    foreach ($groups as $group) {
      $groupOperator = $group['operator'];
      $filterSelects = [];
      foreach ($group['filters'] as $filter) {
        $subscribersIdsQuery = $this->entityManager
          ->getConnection()
          ->createQueryBuilder()
          ->select("DISTINCT $subscribersTable.id as inner_subscriber_id")
          ->from($subscribersTable);
        // When a required plugin is missing inside a NONE group, we cannot evaluate the
        // negation reliably — fail closed and return empty.
        $missingPlugins = $this->segmentDependencyValidator->getMissingPluginsByFilter($filter);
        if ($missingPlugins && $groupOperator === DynamicSegmentFilterData::CONNECT_TYPE_NONE) {
          return $queryBuilder->andWhere('1 = 0');
        }
        if ($missingPlugins) {
          $subscribersIdsQuery->andWhere('1 = 0');
        } else {
          $this->filterFactory->getFilterForFilterEntity($filter)->apply($subscribersIdsQuery, $filter);
        }
        $filterSelects[] = $subscribersIdsQuery->getSQL();
        $queryBuilder->setParameters(
          array_merge(
            $subscribersIdsQuery->getParameters(),
            $queryBuilder->getParameters()
          ),
          array_merge(
            $subscribersIdsQuery->getParameterTypes(),
            $queryBuilder->getParameterTypes()
          )
        );
      }
      if ($filterSelects) {
        $builtGroups[] = ['operator' => $groupOperator, 'selects' => $filterSelects];
      }
    }

    // Fast path: a single group — emit its filter subqueries directly via joinSubqueries
    // with the group's own operator. Covers every legacy segment (which always collapses
    // to one group) and avoids wrapping the filter selects in an extra derived table.
    // Preserves the SQL shape MySQL was previously optimizing against.
    if (count($builtGroups) === 1) {
      $only = $builtGroups[0];
      $this->joinSubqueries($queryBuilder, $only['operator'], $only['selects']);
      return $queryBuilder;
    }

    $groupSelects = [];
    foreach ($builtGroups as $built) {
      $groupSelects[] = $this->combineGroupFilters($built['operator'], $built['selects'], $subscribersTable);
    }

    $outerOperator = $segment->getFiltersConnectOperator();
    // Multi-group segments must use AND/OR as the outer connector — FilterDataMapper
    // rejects NONE in that case. Coerce defensively in case unusual data slips through.
    if ($outerOperator === DynamicSegmentFilterData::CONNECT_TYPE_NONE) {
      $outerOperator = DynamicSegmentFilterData::CONNECT_TYPE_AND;
    }
    $this->joinSubqueries($queryBuilder, $outerOperator, $groupSelects);
    return $queryBuilder;
  }

  /**
   * Combines filter subqueries within a single group into one derived-table SQL string
   * that yields `inner_subscriber_id` rows.
   *
   * @param string[] $filterSelects
   */
  private function combineGroupFilters(string $groupOperator, array $filterSelects, string $subscribersTable): string {
    if ($groupOperator === DynamicSegmentFilterData::CONNECT_TYPE_NONE) {
      $unionSql = join(' UNION ', $filterSelects);
      return sprintf(
        "SELECT %s.id AS inner_subscriber_id FROM %s LEFT JOIN (%s) excluded_subscribers ON excluded_subscribers.inner_subscriber_id = %s.id WHERE excluded_subscribers.inner_subscriber_id IS NULL",
        $subscribersTable,
        $subscribersTable,
        $unionSql,
        $subscribersTable
      );
    }

    if (count($filterSelects) === 1) {
      return $filterSelects[0];
    }

    if ($groupOperator === DynamicSegmentFilterData::CONNECT_TYPE_OR) {
      return join(' UNION ', $filterSelects);
    }

    // AND: chain INNER JOINs on inner_subscriber_id
    $sql = sprintf('SELECT a0.inner_subscriber_id FROM (%s) a0', array_shift($filterSelects));
    $index = 1;
    foreach ($filterSelects as $filterSelect) {
      $sql .= sprintf(
        ' INNER JOIN (%s) a%d ON a%d.inner_subscriber_id = a0.inner_subscriber_id',
        $filterSelect,
        $index,
        $index
      );
      $index++;
    }
    return $sql;
  }

  private function joinSubqueries(QueryBuilder $queryBuilder, string $filtersConnectOperator, array $subQueries): QueryBuilder {
    if (!$subQueries) return $queryBuilder;
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    if ($filtersConnectOperator === DynamicSegmentFilterData::CONNECT_TYPE_OR) {
      // the final query: SELECT * FROM subscribers INNER JOIN (filter_select1 UNION filter_select2) filtered_subscribers ON filtered_subscribers.inner_subscriber_id = id
      $queryBuilder->innerJoin(
        $subscribersTable,
        sprintf('(%s)', join(' UNION ', $subQueries)),
        'filtered_subscribers',
        "filtered_subscribers.inner_subscriber_id = $subscribersTable.id"
      );
      return $queryBuilder;
    }

    if ($filtersConnectOperator === DynamicSegmentFilterData::CONNECT_TYPE_NONE) {
      $queryBuilder->leftJoin(
        $subscribersTable,
        sprintf('(%s)', join(' UNION ', $subQueries)),
        'filtered_subscribers',
        "filtered_subscribers.inner_subscriber_id = $subscribersTable.id"
      )
        ->andWhere('filtered_subscribers.inner_subscriber_id IS NULL');
      return $queryBuilder;
    }

    foreach ($subQueries as $key => $subQuery) {
      // we need a unique name for each subquery so that we can join them together in the sql query - just make sure the identifier starts with a letter, not a number
      $subqueryName = 'a' . $key;
      $queryBuilder->innerJoin(
        $subscribersTable,
        "($subQuery)",
        $subqueryName,
        "$subqueryName.inner_subscriber_id = $subscribersTable.id"
      );
    }
    return $queryBuilder;
  }
}
