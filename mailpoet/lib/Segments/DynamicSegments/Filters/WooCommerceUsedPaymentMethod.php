<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\WooCommerce\Helper;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use WC_Payment_Gateway;

class WooCommerceUsedPaymentMethod implements Filter {
  const ACTION = 'usedPaymentMethod';

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
    $paymentMethods = $filterData->getParam('payment_methods');

    if (!is_string($operator) || !in_array($operator, self::VALID_OPERATORS, true)) {
      throw new InvalidFilterException('Invalid operator', InvalidFilterException::MISSING_OPERATOR);
    }

    if (!is_array($paymentMethods) || count($paymentMethods) < 1) {
      throw new InvalidFilterException('Missing payment methods', InvalidFilterException::MISSING_VALUE);
    }

    $data = $filterData->getData();
    $this->filterHelper->validateDaysPeriodData((array)$data);

    $excludedStatuses = $this->wooFilterHelper->defaultExcludedStatuses();

    switch ($operator) {
      case DynamicSegmentFilterData::OPERATOR_ANY:
        $this->applyForAnyOperator($queryBuilder, $excludedStatuses, $paymentMethods, $filterData);
        break;
      case DynamicSegmentFilterData::OPERATOR_ALL:
        $this->applyForAllOperator($queryBuilder, $excludedStatuses, $paymentMethods, $filterData);
        break;
      case DynamicSegmentFilterData::OPERATOR_NONE:
        $subQuery = $this->filterHelper->getNewSubscribersQueryBuilder();
        $this->applyForAnyOperator($subQuery, $excludedStatuses, $paymentMethods, $filterData);
        $subscribersTable = $this->filterHelper->getSubscribersTable();
        $queryBuilder->andWhere($queryBuilder->expr()->notIn("$subscribersTable.id", $this->filterHelper->getInterpolatedSQL($subQuery)));
        break;
    }

    return $queryBuilder;
  }

  private function applyForAnyOperator(QueryBuilder $queryBuilder, array $excludedStatuses, array $paymentMethods, DynamicSegmentFilterData $filterData): void {
    if ($this->wooHelper->isWooCommerceCustomOrdersTableEnabled()) {
      $this->applyCustomOrderTableJoin($queryBuilder, $excludedStatuses, $paymentMethods, $filterData);
    } else {
      $this->applyPostmetaOrderJoin($queryBuilder, $excludedStatuses, $paymentMethods, $filterData);
    }
  }

  private function applyForAllOperator(QueryBuilder $queryBuilder, array $excludedStatuses, array $paymentMethods, DynamicSegmentFilterData $filterData): void {
    if ($this->wooHelper->isWooCommerceCustomOrdersTableEnabled()) {
      $ordersAlias = $this->applyCustomOrderTableJoin($queryBuilder, $excludedStatuses, $paymentMethods, $filterData);
      $queryBuilder->groupBy('inner_subscriber_id')
        ->having("COUNT(DISTINCT $ordersAlias.payment_method) = " . count($paymentMethods));
    } else {
      $postmetaAlias = $this->applyPostmetaOrderJoin($queryBuilder, $excludedStatuses, $paymentMethods, $filterData);
      $queryBuilder->groupBy('inner_subscriber_id')->having("COUNT(DISTINCT $postmetaAlias.meta_value) = " . count($paymentMethods));
    }
  }

  private function applyPostmetaOrderJoin(QueryBuilder $queryBuilder, array $excludedStatuses, array $paymentMethods, DynamicSegmentFilterData $filterData, string $postmetaAlias = 'postmeta'): string {
    $paymentMethodParam = $this->filterHelper->getUniqueParameterName('paymentMethod');
    $paymentMethodMetaKeyParam = $this->filterHelper->getUniqueParameterName('paymentMethod');

    $postMetaTable = $this->filterHelper->getPrefixedTable('postmeta');
    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder, $excludedStatuses);
    $queryBuilder
      ->innerJoin($orderStatsAlias, $postMetaTable, $postmetaAlias, "$orderStatsAlias.order_id = $postmetaAlias.post_id")
      ->andWhere("postmeta.meta_key = :$paymentMethodMetaKeyParam")
      ->andWhere("postmeta.meta_value IN (:$paymentMethodParam)")
      ->setParameter($paymentMethodMetaKeyParam, '_payment_method')
      ->setParameter($paymentMethodParam, $paymentMethods, ArrayParameterType::STRING);
    $this->filterHelper->applyDatePeriodFilter($queryBuilder, "$orderStatsAlias.date_created", $filterData);
    return $postmetaAlias;
  }

  private function applyCustomOrderTableJoin(QueryBuilder $queryBuilder, array $excludedStatuses, array $paymentMethods, DynamicSegmentFilterData $filterData, string $ordersAlias = 'orders'): string {
    $paymentMethodParam = $this->filterHelper->getUniqueParameterName('paymentMethod');
    $ordersTable = $this->wooHelper->getOrdersTableName();
    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder, $excludedStatuses);
    $queryBuilder
      ->innerJoin($orderStatsAlias, $ordersTable, 'orders', "$orderStatsAlias.order_id = orders.id")
      ->andWhere("$ordersAlias.payment_method IN (:$paymentMethodParam)")
      ->setParameter($paymentMethodParam, $paymentMethods, ArrayParameterType::STRING);
    $this->filterHelper->applyDatePeriodFilter($queryBuilder, "$orderStatsAlias.date_created", $filterData);
    return $ordersAlias;
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    $lookupData = [
      'paymentMethods' => [],
    ];
    if (!$this->wooHelper->isWooCommerceActive()) {
      return $lookupData;
    }
    $paymentMethods = $filterData->getArrayParam('payment_methods');
    $allGateways = $this->wooHelper->getPaymentGateways()->payment_gateways();

    foreach ($paymentMethods as $paymentMethod) {
      if (isset($allGateways[$paymentMethod]) && $allGateways[$paymentMethod] instanceof WC_Payment_Gateway) {
        $lookupData['paymentMethods'][$paymentMethod] = $allGateways[$paymentMethod]->get_method_title();
      }
    }

    return $lookupData;
  }
}
