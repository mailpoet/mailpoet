<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\Security;
use MailPoet\WooCommerce\Helper;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use WC_Product;

class WooCommerceProduct implements Filter {
  const ACTION_PRODUCT = 'purchasedProduct';

  /** @var EntityManager */
  private $entityManager;

  /** @var WooFilterHelper */
  private $wooFilterHelper;

  /** @var FilterHelper */
  private $filterHelper;

  /** @var Helper */
  private $wooHelper;

  public function __construct(
    EntityManager $entityManager,
    FilterHelper $filterHelper,
    Helper $wooHelper,
    WooFilterHelper $wooFilterHelper
  ) {
    $this->entityManager = $entityManager;
    $this->wooFilterHelper = $wooFilterHelper;
    $this->filterHelper = $filterHelper;
    $this->wooHelper = $wooHelper;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $operator = $filterData->getOperator();
    $productIds = $filterData->getParam('product_ids');
    $productIds = is_array($productIds) ? $productIds : [];
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();

    if ($operator === DynamicSegmentFilterData::OPERATOR_ANY) {
      $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder);
      $this->filterHelper->applyDatePeriodFilter($queryBuilder, "$orderStatsAlias.date_created", $filterData, false, DynamicSegmentFilterData::TIMEFRAME_ALL_TIME);
      $this->applyProductJoin($queryBuilder, $orderStatsAlias);
      $queryBuilder->andWhere("product.product_id IN (:products_{$parameterSuffix})");
    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_ALL) {
      $subQueryCount = 1;
      foreach ($productIds as $productId) {
        $uniqueParameterSuffix = Security::generateRandomString();
        $subQuery = $this->filterHelper->getNewSubscribersQueryBuilder();
        $subOrderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($subQuery);
        $this->filterHelper->applyDatePeriodFilter($subQuery, "$subOrderStatsAlias.date_created", $filterData, false, DynamicSegmentFilterData::TIMEFRAME_ALL_TIME);
        $this->applyProductJoin($subQuery, $subOrderStatsAlias);
        $subQuery->andWhere("product.product_id = :product_{$uniqueParameterSuffix}");
        $subQuery->setParameter("product_{$uniqueParameterSuffix}", $productId);
        $alias = sprintf('productSubQuery%d', $subQueryCount);
        $queryBuilder->innerJoin(
          $subscribersTable,
          sprintf('(%s)', $this->filterHelper->getInterpolatedSQL($subQuery)),
          $alias,
          "$subscribersTable.id = $alias.id"
        );
        $subQueryCount++;
      }
    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_NONE) {
      // subQuery with subscriber ids that bought products
      $subQuery = $this->createQueryBuilder($subscribersTable);
      $subQuery->select("DISTINCT $subscribersTable.id");
      $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($subQuery);
      $this->filterHelper->applyDatePeriodFilter($subQuery, "$orderStatsAlias.date_created", $filterData, false, DynamicSegmentFilterData::TIMEFRAME_ALL_TIME);
      $subQuery = $this->applyProductJoin($subQuery, $orderStatsAlias);
      $subQuery->andWhere("product.product_id IN (:products_{$parameterSuffix})");
      // application subQuery for negation
      $queryBuilder->where("{$subscribersTable}.id NOT IN ({$this->filterHelper->getInterpolatedSQL($subQuery)})");
    }
    return $queryBuilder
      ->setParameter("products_{$parameterSuffix}", $productIds, ArrayParameterType::STRING);
  }

  private function applyProductJoin(QueryBuilder $queryBuilder, string $orderStatsAlias): QueryBuilder {
    global $wpdb;
    return $queryBuilder->innerJoin(
      $orderStatsAlias,
      $wpdb->prefix . 'wc_order_product_lookup',
      'product',
      "$orderStatsAlias.order_id = product.order_id"
    );
  }

  private function createQueryBuilder(string $table): QueryBuilder {
    return $this->entityManager->getConnection()
      ->createQueryBuilder()
      ->from($table);
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    $lookupData = ['products' => []];
    if (!$this->wooHelper->isWooCommerceActive()) {
      return $lookupData;
    }
    $productIds = $filterData->getArrayParam('product_ids');
    foreach ($productIds as $productId) {
      $product = $this->wooHelper->wcGetProduct($productId);
      if ($product instanceof WC_Product) {
        $lookupData['products'][$productId] = $product->get_name();
      }
    }

    return $lookupData;
  }
}
