<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooCommerceUsedCouponCode implements Filter {
  const ACTION = 'usedCouponCode';

  const COUPON_CODE_IDS_KEY = 'coupon_code_ids';

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
    $this->validateFilterData((array)$filterData->getData());

    $operator = $filterData->getParam('operator');

    switch ($operator) {
      case DynamicSegmentFilterData::OPERATOR_ALL:
        $this->applyForAllOperator($queryBuilder, $filter);
        break;
      case DynamicSegmentFilterData::OPERATOR_ANY:
        $this->applyForAnyOperator($queryBuilder, $filter);
        break;
      case DynamicSegmentFilterData::OPERATOR_NONE:
        $subQuery = $this->filterHelper->getNewSubscribersQueryBuilder();
        $this->applyForAnyOperator($subQuery, $filter);
        $subscribersTable = $this->filterHelper->getSubscribersTable();
        $queryBuilder->andWhere($queryBuilder->expr()->notIn("$subscribersTable.id", $this->filterHelper->getInterpolatedSQL($subQuery)));
        break;
    }

    return $queryBuilder;
  }

  private function applyForAnyOperator(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): void {
    $filterData = $filter->getFilterData();
    $couponIds = (array)$filterData->getParam(self::COUPON_CODE_IDS_KEY);
    $isAllTime = $filterData->getParam('timeframe') === DynamicSegmentFilterData::TIMEFRAME_ALL_TIME;

    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder);

    if (!$isAllTime) {
      /** @var int $days */
      $days = $filterData->getParam('days');
      $date = Carbon::now()->subDays($days);
      $dateParam = $this->filterHelper->getUniqueParameterName('date');
      $queryBuilder->andWhere("$orderStatsAlias.date_created >= :$dateParam")
        ->setParameter($dateParam, $date->toDateTimeString());
    }

    $queryBuilder->innerJoin(
      $orderStatsAlias,
      $this->filterHelper->getPrefixedTable('wc_order_coupon_lookup'),
      'couponLookup',
      "$orderStatsAlias.order_id = couponLookup.order_id"
    );
    $couponCodeIdsParam = $this->filterHelper->getUniqueParameterName('couponCodeIds');
    $queryBuilder
      ->andWhere("couponLookup.coupon_id IN (:$couponCodeIdsParam)")
      ->setParameter($couponCodeIdsParam, $couponIds, Connection::PARAM_INT_ARRAY);
  }

  private function applyForAllOperator(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): void {
    $this->applyForAnyOperator($queryBuilder, $filter);

    $filterData = $filter->getFilterData();
    $couponIds = (array)$filterData->getParam(self::COUPON_CODE_IDS_KEY);
    $queryBuilder->groupBy('inner_subscriber_id')
      ->having("COUNT(DISTINCT couponLookup.coupon_id) = " . count(array_unique($couponIds)));
  }

  public function validateFilterData(array $data): void {
    $this->filterHelper->validateDaysPeriodData($data);
    $couponCodeIds = $data[self::COUPON_CODE_IDS_KEY] ?? [];
    if (count($couponCodeIds) === 0) {
      throw new InvalidFilterException('Missing coupon code IDs', InvalidFilterException::MISSING_VALUE);
    }

    $operator = $data['operator'] ?? null;

    if (
      !in_array($operator, [
      DynamicSegmentFilterData::OPERATOR_ANY,
      DynamicSegmentFilterData::OPERATOR_ALL,
      DynamicSegmentFilterData::OPERATOR_NONE,
      ])
    ) {
      throw new InvalidFilterException('Missing operator', InvalidFilterException::MISSING_OPERATOR);
    }
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    return [];
  }
}
