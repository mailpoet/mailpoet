<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooCommerceSingleOrderValue implements Filter {
  const ACTION_SINGLE_ORDER_VALUE = 'singleOrderValue';

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
    $type = $filterData->getParam('single_order_value_type');
    $amount = $filterData->getParam('single_order_value_amount');
    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();

    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder);
    $this->filterHelper->applyDatePeriodFilter($queryBuilder, "$orderStatsAlias.date_created", $filterData);

    if ($type === '=') {
      $queryBuilder->andWhere("$orderStatsAlias.total_sales = :amount" . $parameterSuffix);
    } elseif ($type === '!=') {
      $queryBuilder->andWhere("$orderStatsAlias.total_sales != :amount" . $parameterSuffix);
    } elseif ($type === '>') {
      $queryBuilder->andWhere("$orderStatsAlias.total_sales > :amount" . $parameterSuffix);
    } elseif ($type === '>=') {
      $queryBuilder->andWhere("$orderStatsAlias.total_sales >= :amount" . $parameterSuffix);
    } elseif ($type === '<') {
      $queryBuilder->andWhere("$orderStatsAlias.total_sales < :amount" . $parameterSuffix);
    } elseif ($type === '<=') {
      $queryBuilder->andWhere("$orderStatsAlias.total_sales <= :amount" . $parameterSuffix);
    }

    $queryBuilder->setParameter('amount' . $parameterSuffix, $amount);

    return $queryBuilder;
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    return [];
  }
}
