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
use WC_Product_Variation;

class WooCommerceProductVariation implements Filter {
  const ACTION_PRODUCT_VARIATION = 'purchasedProductVariation';

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
    $variationIds = $filterData->getParam('variation_ids');
    $variationIds = is_array($variationIds) ? $variationIds : [];
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();

    if ($operator === DynamicSegmentFilterData::OPERATOR_ANY) {
      $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder);
      $this->filterHelper->applyDatePeriodFilter($queryBuilder, "$orderStatsAlias.date_created", $filterData, false, DynamicSegmentFilterData::TIMEFRAME_ALL_TIME);
      $this->applyProductJoin($queryBuilder, $orderStatsAlias);
      $queryBuilder->andWhere("product.variation_id IN (:variations_{$parameterSuffix})");
    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_ALL) {
      $subQueryCount = 1;
      foreach ($variationIds as $variationId) {
        $uniqueParameterSuffix = Security::generateRandomString();
        $subQuery = $this->filterHelper->getNewSubscribersQueryBuilder();
        $subOrderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($subQuery);
        $this->filterHelper->applyDatePeriodFilter($subQuery, "$subOrderStatsAlias.date_created", $filterData, false, DynamicSegmentFilterData::TIMEFRAME_ALL_TIME);
        $this->applyProductJoin($subQuery, $subOrderStatsAlias);
        $subQuery->andWhere("product.variation_id = :variation_{$uniqueParameterSuffix}");
        $subQuery->setParameter("variation_{$uniqueParameterSuffix}", $variationId);
        $alias = sprintf('variationSubQuery%d', $subQueryCount);
        $queryBuilder->innerJoin(
          $subscribersTable,
          sprintf('(%s)', $this->filterHelper->getInterpolatedSQL($subQuery)),
          $alias,
          "$subscribersTable.id = $alias.id"
        );
        $subQueryCount++;
      }
    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_NONE) {
      $subQuery = $this->createQueryBuilder($subscribersTable);
      $subQuery->select("DISTINCT $subscribersTable.id");
      $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($subQuery);
      $this->filterHelper->applyDatePeriodFilter($subQuery, "$orderStatsAlias.date_created", $filterData, false, DynamicSegmentFilterData::TIMEFRAME_ALL_TIME);
      $subQuery = $this->applyProductJoin($subQuery, $orderStatsAlias);
      $subQuery->andWhere("product.variation_id IN (:variations_{$parameterSuffix})");
      $queryBuilder->where("{$subscribersTable}.id NOT IN ({$this->filterHelper->getInterpolatedSQL($subQuery)})");
    }
    return $queryBuilder
      ->setParameter("variations_{$parameterSuffix}", $variationIds, ArrayParameterType::STRING);
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
    $lookupData = ['variations' => []];
    if (!$this->wooHelper->isWooCommerceActive()) {
      return $lookupData;
    }
    $variationIds = $filterData->getArrayParam('variation_ids');
    foreach ($variationIds as $variationId) {
      $variation = $this->wooHelper->wcGetProduct($variationId);
      if (!$variation instanceof WC_Product) {
        continue;
      }
      $lookupData['variations'][$variationId] = $this->formatVariationLabel($variation);
    }

    return $lookupData;
  }

  private function formatVariationLabel(WC_Product $variation): string {
    if (!$variation instanceof WC_Product_Variation) {
      return $variation->get_name();
    }
    $parent = $this->wooHelper->wcGetProduct($variation->get_parent_id());
    $parentName = $parent instanceof WC_Product ? $parent->get_name() : $variation->get_name();
    $attributesSummary = $this->wooHelper->wcGetFormattedVariation($variation, true);
    return $attributesSummary === '' ? $parentName : sprintf('%s — %s', $parentName, $attributesSummary);
  }
}
