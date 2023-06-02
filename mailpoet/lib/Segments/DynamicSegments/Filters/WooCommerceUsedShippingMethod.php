<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\WooCommerce\Helper;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooCommerceUsedShippingMethod implements Filter {
  const ACTION = 'usedShippingMethod';

  const VALID_OPERATORS = [
    DynamicSegmentFilterData::OPERATOR_NONE,
    DynamicSegmentFilterData::OPERATOR_ANY,
    DynamicSegmentFilterData::OPERATOR_ALL,
  ];

  /** @var WooFilterHelper */
  private $wooFilterHelper;

  /** @var Helper */
  private $wooHelper;

  /** @var FilterHelper */
  private $filterHelper;

  public function __construct(
    FilterHelper $filterHelper,
    WooFilterHelper $wooFilterHelper,
    Helper $wooHelper
  ) {
    $this->wooFilterHelper = $wooFilterHelper;
    $this->wooHelper = $wooHelper;
    $this->filterHelper = $filterHelper;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $operator = $filterData->getParam('operator');
    $shippingMethods = $filterData->getParam('shipping_methods');
    $days = $filterData->getParam('used_shipping_method_days');

    if (!is_string($operator) || !in_array($operator, self::VALID_OPERATORS, true)) {
      throw new InvalidFilterException('Invalid operator', InvalidFilterException::MISSING_OPERATOR);
    }

    if (!is_array($shippingMethods) || count($shippingMethods) < 1) {
      throw new InvalidFilterException('Missing payment methods', InvalidFilterException::MISSING_VALUE);
    }

    if (!is_int($days) || $days < 1) {
      throw new InvalidFilterException('Missing days', InvalidFilterException::MISSING_VALUE);
    }

    $includedStatuses = array_keys($this->wooHelper->getOrderStatuses());
    $failedKey = array_search('wc-failed', $includedStatuses, true);
    if ($failedKey !== false) {
      unset($includedStatuses[$failedKey]);
    }
    $date = Carbon::now()->subDays($days);

    switch ($operator) {
      case DynamicSegmentFilterData::OPERATOR_ANY:
        $this->applyForAnyOperator($queryBuilder, $includedStatuses, $shippingMethods, $date);
        break;
      case DynamicSegmentFilterData::OPERATOR_ALL:
        $this->applyForAllOperator($queryBuilder, $includedStatuses, $shippingMethods, $date);
        break;
      case DynamicSegmentFilterData::OPERATOR_NONE:
        $this->applyForNoneOperator($queryBuilder, $includedStatuses, $shippingMethods, $date);
        break;
    }

    return $queryBuilder;
  }

  private function applyForAnyOperator(QueryBuilder $queryBuilder, array $includedStatuses, array $shippingMethods, Carbon $date): void {
    $dateParam = $this->filterHelper->getUniqueParameterName('date');
    $shippingMethodParam = $this->filterHelper->getUniqueParameterName('shippingMethod');

    $orderItemsTable = $this->filterHelper->getPrefixedTable('woocommerce_order_items');
    $orderItemsTableAlias = 'orderItems';
    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder, $includedStatuses);
    $queryBuilder
      ->innerJoin($orderStatsAlias, $orderItemsTable, $orderItemsTableAlias, "$orderStatsAlias.order_id = $orderItemsTableAlias.order_id")
      ->andWhere("$orderStatsAlias.date_created >= :$dateParam")
      ->andWhere("$orderItemsTableAlias.order_item_name IN (:$shippingMethodParam)")
      ->andWhere("$orderItemsTableAlias.order_item_type = 'shipping'")
      ->setParameter($dateParam, $date->toDateTimeString())
      ->setParameter($shippingMethodParam, $shippingMethods, Connection::PARAM_STR_ARRAY);
  }

  private function applyForAllOperator(QueryBuilder $queryBuilder, array $includedStatuses, array $shippingMethods, Carbon $date): void {
    $dateParam = $this->filterHelper->getUniqueParameterName('date');
    $orderItemTypeParam = $this->filterHelper->getUniqueParameterName('orderItemType');
    $shippingMethodsParam = $this->filterHelper->getUniqueParameterName('shippingMethod');

    $orderItemsTable = $this->filterHelper->getPrefixedTable('woocommerce_order_items');
    $orderItemsAlias = 'orderItems';
    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder, $includedStatuses);
    $queryBuilder
      ->innerJoin($orderStatsAlias, $orderItemsTable, $orderItemsAlias, "$orderStatsAlias.order_id = $orderItemsAlias.order_id")
      ->andWhere("$orderStatsAlias.date_created >= :$dateParam")
      ->andWhere("$orderItemsAlias.order_item_type = :$orderItemTypeParam")
      ->andWhere("$orderItemsAlias.order_item_name IN (:$shippingMethodsParam)")
      ->setParameter($dateParam, $date->toDateTimeString())
      ->setParameter($orderItemTypeParam, 'shipping')
      ->setParameter($shippingMethodsParam, $shippingMethods, Connection::PARAM_STR_ARRAY)
      ->groupBy('inner_subscriber_id')->having("COUNT(DISTINCT $orderItemsAlias.order_item_name) = " . count($shippingMethods));
  }

  private function applyForNoneOperator(QueryBuilder $queryBuilder, array $includedStatuses, array $shippingMethods, Carbon $date): void {
    $subQuery = $this->filterHelper->getNewSubscribersQueryBuilder();
    $this->applyForAnyOperator($subQuery, $includedStatuses, $shippingMethods, $date);
    $subscribersTable = $this->filterHelper->getSubscribersTable();
    $queryBuilder->andWhere($queryBuilder->expr()->notIn("$subscribersTable.id", $this->filterHelper->getInterpolatedSQL($subQuery)));
  }
}
