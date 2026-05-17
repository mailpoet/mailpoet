<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class NumberOfClicks implements Filter {
  const ACTION = 'numberOfClicks';

  /** @var EntityManager */
  private $entityManager;

  /** @var FilterHelper */
  private $filterHelper;

  public function __construct(
    EntityManager $entityManager,
    FilterHelper $filterHelper
  ) {
    $this->entityManager = $entityManager;
    $this->filterHelper = $filterHelper;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $clickCount = $filterData->getIntParam('clicks');
    $operator = $filterData->getStringParam('operator');
    $statsTable = $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName();
    $subscribersTable = $this->filterHelper->getSubscribersTable();
    $dateCondition = $this->filterHelper->getDatePeriodCondition($queryBuilder, 'clicks.created_at', $filterData, true);
    $joinCondition = "{$subscribersTable}.id = clicks.subscriber_id";
    if ($dateCondition !== null) {
      $joinCondition .= " AND $dateCondition";
    }

    $queryBuilder->leftJoin($subscribersTable, $statsTable, 'clicks', $joinCondition);

    $queryBuilder->groupBy("$subscribersTable.id");
    $clicksCountParam = $this->filterHelper->getUniqueParameterName('clicks');

    if ($operator === 'equals') {
      $queryBuilder->having("count(clicks.id) = :$clicksCountParam");
    } else if ($operator === 'not_equals') {
      $queryBuilder->having("count(clicks.id) != :$clicksCountParam");
    } else if ($operator === 'less') {
      $queryBuilder->having("count(clicks.id) < :$clicksCountParam");
    } else {
      $queryBuilder->having("count(clicks.id) > :$clicksCountParam");
    }
    $queryBuilder->setParameter($clicksCountParam, $clickCount);

    return $queryBuilder;
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    return [];
  }
}
