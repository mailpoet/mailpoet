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
    list($shippingMethodIds, $instanceIds) = $this->extractShippingMethodIdsAndInstanceIds((array)$filterData->getParam('shipping_methods'));

    $days = $filterData->getParam('used_shipping_method_days');

    if (!is_string($operator) || !in_array($operator, self::VALID_OPERATORS, true)) {
      throw new InvalidFilterException('Invalid operator', InvalidFilterException::MISSING_OPERATOR);
    }

    if (!is_array($shippingMethodIds) || empty($shippingMethodIds) || !is_array($instanceIds) || empty($instanceIds)) {
      throw new InvalidFilterException('Missing shipping methods', InvalidFilterException::MISSING_VALUE);
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
        $this->applyForAnyOperator($queryBuilder, $includedStatuses, $shippingMethodIds, $instanceIds, $date);
        break;
      case DynamicSegmentFilterData::OPERATOR_ALL:
        $this->applyForAllOperator($queryBuilder, $includedStatuses, $shippingMethodIds, $instanceIds, $date);
        break;
      case DynamicSegmentFilterData::OPERATOR_NONE:
        $this->applyForNoneOperator($queryBuilder, $includedStatuses, $shippingMethodIds, $instanceIds, $date);
        break;
    }

    return $queryBuilder;
  }

  private function applyForAnyOperator(QueryBuilder $queryBuilder, array $includedStatuses, array $shippingMethodIds, array $instanceIds, Carbon $date): void {
    $dateParam = $this->filterHelper->getUniqueParameterName('date');
    $shippingMethodsParam = $this->filterHelper->getUniqueParameterName('shippingMethods');
    $instanceIdsParam = $this->filterHelper->getUniqueParameterName('instanceIds');

    $orderItemsTable = $this->filterHelper->getPrefixedTable('woocommerce_order_items');
    $orderItemsTableAlias = 'orderItems';
    $orderItemMetaTable = $this->filterHelper->getPrefixedTable('woocommerce_order_itemmeta');
    $orderItemMetaTableAlias1 = 'orderItemMeta1';
    $orderItemMetaTableAlias2 = 'orderItemMeta2';
    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder, $includedStatuses);
    $queryBuilder
      ->innerJoin($orderStatsAlias, $orderItemsTable, $orderItemsTableAlias, "$orderStatsAlias.order_id = $orderItemsTableAlias.order_id")
      ->innerJoin($orderItemsTableAlias, $orderItemMetaTable, $orderItemMetaTableAlias1, "$orderItemsTableAlias.order_item_id = $orderItemMetaTableAlias1.order_item_id")
      ->innerJoin($orderItemsTableAlias, $orderItemMetaTable, $orderItemMetaTableAlias2, "$orderItemsTableAlias.order_item_id = $orderItemMetaTableAlias2.order_item_id")
      ->andWhere("$orderStatsAlias.date_created >= :$dateParam")
      ->andWhere("$orderItemsTableAlias.order_item_type = 'shipping'")
      ->andWhere("$orderItemMetaTableAlias1.meta_key = 'method_id'")
      ->andWhere("$orderItemMetaTableAlias1.meta_value IN (:$shippingMethodsParam)")
      ->andWhere("$orderItemMetaTableAlias2.meta_key = 'instance_id'")
      ->andWhere("$orderItemMetaTableAlias2.meta_value IN (:$instanceIdsParam)")
      ->setParameter($dateParam, $date->toDateTimeString())
      ->setParameter($shippingMethodsParam, $shippingMethodIds, Connection::PARAM_STR_ARRAY)
      ->setParameter($instanceIdsParam, $instanceIds, Connection::PARAM_STR_ARRAY);
  }

  private function applyForAllOperator(QueryBuilder $queryBuilder, array $includedStatuses, array $shippingMethodIds, array $instanceIds, Carbon $date): void {
    $dateParam = $this->filterHelper->getUniqueParameterName('date');
    $orderItemTypeParam = $this->filterHelper->getUniqueParameterName('orderItemType');
    $shippingMethodsParam = $this->filterHelper->getUniqueParameterName('shippingMethods');
    $instanceIdsParam = $this->filterHelper->getUniqueParameterName('instanceIds');

    $orderItemsTable = $this->filterHelper->getPrefixedTable('woocommerce_order_items');
    $orderItemsTableAlias = 'orderItems';
    $orderItemMetaTable = $this->filterHelper->getPrefixedTable('woocommerce_order_itemmeta');
    $orderItemMetaTableAlias1 = 'orderItemMeta1';
    $orderItemMetaTableAlias2 = 'orderItemMeta2';
    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder, $includedStatuses);

    $queryBuilder
      ->innerJoin($orderStatsAlias, $orderItemsTable, $orderItemsTableAlias, "$orderStatsAlias.order_id = $orderItemsTableAlias.order_id")
      ->innerJoin($orderItemsTableAlias, $orderItemMetaTable, $orderItemMetaTableAlias1, "$orderItemsTableAlias.order_item_id = $orderItemMetaTableAlias1.order_item_id")
      ->innerJoin($orderItemsTableAlias, $orderItemMetaTable, $orderItemMetaTableAlias2, "$orderItemsTableAlias.order_item_id = $orderItemMetaTableAlias2.order_item_id")
      ->andWhere("$orderStatsAlias.date_created >= :$dateParam")
      ->andWhere("$orderItemsTableAlias.order_item_type = :$orderItemTypeParam")
      ->andWhere("$orderItemMetaTableAlias1.meta_key = 'method_id'")
      ->andWhere("$orderItemMetaTableAlias1.meta_value IN (:$shippingMethodsParam)")
      ->andWhere("$orderItemMetaTableAlias2.meta_key = 'instance_id'")
      ->andWhere("$orderItemMetaTableAlias2.meta_value IN (:$instanceIdsParam)")
      ->setParameter($dateParam, $date->toDateTimeString())
      ->setParameter($orderItemTypeParam, 'shipping')
      ->setParameter($shippingMethodsParam, $shippingMethodIds, Connection::PARAM_STR_ARRAY)
      ->setParameter($instanceIdsParam, $instanceIds, Connection::PARAM_STR_ARRAY)
      ->groupBy('inner_subscriber_id')
      ->having("COUNT(DISTINCT(CONCAT($orderItemMetaTableAlias1.meta_value, $orderItemMetaTableAlias2.meta_value))) = " . count($shippingMethodIds));
  }

  private function applyForNoneOperator(QueryBuilder $queryBuilder, array $includedStatuses, array $shippingMethodIds, array $instanceIds, Carbon $date): void {
    $subQuery = $this->filterHelper->getNewSubscribersQueryBuilder();
    $this->applyForAnyOperator($subQuery, $includedStatuses, $shippingMethodIds, $instanceIds, $date);
    $subscribersTable = $this->filterHelper->getSubscribersTable();
    $queryBuilder->andWhere($queryBuilder->expr()->notIn("$subscribersTable.id", $this->filterHelper->getInterpolatedSQL($subQuery)));
  }

  /**
   * Extracts shipping method ids and instance ids from the given array of strings.
   * The format of each shipping method string is "shippingMethod:instanceId". For example,
   * "flat_rate:1" or "local_pickup:2".
   *
   * @param array $shippingMethodStrings
   * @return array[]
   */
  private function extractShippingMethodIdsAndInstanceIds(array $shippingMethodStrings): array {
    $shippingMethodIds = [];
    $instanceIds = [];

    foreach ($shippingMethodStrings as $shippingMethodString) {
      if (preg_match('/^\w+:\d+$/', $shippingMethodString)) {
        $parts = preg_split('/:/', $shippingMethodString);

        if (is_array($parts) && is_string($parts[0]) && is_string($parts[1])) {
          $shippingMethodIds[] = $parts[0];
          $instanceIds[] = $parts[1];
        }
      }
    }

    return [$shippingMethodIds, $instanceIds];
  }
}
