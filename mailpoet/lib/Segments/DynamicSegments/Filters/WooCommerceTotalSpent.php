<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooCommerceTotalSpent implements Filter {
  const ACTION_TOTAL_SPENT = 'totalSpent';

  /** @var WooFilterHelper */
  private $wooFilterHelper;

  /** @var FilterHelper */
  private $filterHelper;

  public function __construct(
    WooFilterHelper $wooFilterHelper,
    FilterHelper $filterHelper
  ) {
    $this->wooFilterHelper = $wooFilterHelper;
    $this->filterHelper = $filterHelper;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $type = $filterData->getParam('total_spent_type');
    $amount = $filterData->getParam('total_spent_amount');

    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();
    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder);
    $this->filterHelper->applyDatePeriodFilter($queryBuilder, "$orderStatsAlias.date_created", $filterData);

    $queryBuilder->groupBy('inner_subscriber_id');

    if ($type === '=') {
      $queryBuilder->having("SUM($orderStatsAlias.total_sales) = :amount" . $parameterSuffix);
    } elseif ($type === '!=') {
      $queryBuilder->having("SUM($orderStatsAlias.total_sales) != :amount" . $parameterSuffix);
    } elseif ($type === '>') {
      $queryBuilder->having("SUM($orderStatsAlias.total_sales) > :amount" . $parameterSuffix);
    } elseif ($type === '<') {
      $queryBuilder->having("SUM($orderStatsAlias.total_sales) < :amount" . $parameterSuffix);
    }

    $queryBuilder->setParameter('amount' . $parameterSuffix, $amount);

    return $queryBuilder;
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    return [];
  }
}
