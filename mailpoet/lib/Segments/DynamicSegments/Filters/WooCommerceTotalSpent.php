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
    $isAllTime = $filterData->getParam('timeframe') === DynamicSegmentFilterData::TIMEFRAME_ALL_TIME;

    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();
    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder);

    if (!$isAllTime) {
      /** @var int $days - for PHPStan because intval() doesn't accept a value of mixed */
      $days = $filterData->getParam('days');
      $date = $this->filterHelper->getDateNDaysAgo(intval($days));
      $dateParam = "date_$parameterSuffix";
      $queryBuilder->andWhere("$orderStatsAlias.date_created >= :$dateParam")
        ->setParameter($dateParam, $date->toDateTimeString());
    }

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
