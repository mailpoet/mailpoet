<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooCommerceAverageSpent implements Filter {
  const ACTION = 'averageSpent';

  /** @var WooFilterHelper */
  private $wooFilterHelper;

  /** @var FilterHelper */
  private $filterHelper;

  public function __construct(
    FilterHelper $filterHelper,
    WooFilterHelper $wooFilterHelper
  ) {
    $this->filterHelper = $filterHelper;
    $this->wooFilterHelper = $wooFilterHelper;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $operator = $filterData->getParam('average_spent_type');
    $amount = $filterData->getParam('average_spent_amount');

    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder);
    $this->filterHelper->applyDatePeriodFilter($queryBuilder, "$orderStatsAlias.date_created", $filterData);

    $queryBuilder->groupBy('inner_subscriber_id');

    $amountParam = $this->filterHelper->getUniqueParameterName('amount');
    if ($operator === '=') {
      $queryBuilder->having("AVG($orderStatsAlias.total_sales) = :$amountParam");
    } elseif ($operator === '!=') {
      $queryBuilder->having("AVG($orderStatsAlias.total_sales) != :$amountParam");
    } elseif ($operator === '>') {
      $queryBuilder->having("AVG($orderStatsAlias.total_sales) > :$amountParam");
    } elseif ($operator === '<') {
      $queryBuilder->having("AVG($orderStatsAlias.total_sales) < :$amountParam");
    } elseif ($operator === '<=') {
      $queryBuilder->having("AVG($orderStatsAlias.total_sales) <= :$amountParam");
    } elseif ($operator === '>=') {
      $queryBuilder->having("AVG($orderStatsAlias.total_sales) >= :$amountParam");
    }

    $queryBuilder->setParameter($amountParam, $amount);

    return $queryBuilder;
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    return [];
  }
}
