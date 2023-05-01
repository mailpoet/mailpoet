<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoetVendor\Carbon\Carbon;
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
    $days = intval($filterData->getParam('average_spent_days'));

    $date = Carbon::now()->subDays($days);
    $dateParam = $this->filterHelper->getUniqueParameterName('date');
    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder);

    $queryBuilder->andWhere("$orderStatsAlias.date_created >= :$dateParam")
      ->setParameter($dateParam, $date->toDateTimeString())
      ->groupBy('inner_subscriber_id');

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
}
