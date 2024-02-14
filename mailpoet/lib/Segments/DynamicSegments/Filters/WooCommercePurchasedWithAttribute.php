<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\WP\Functions;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooCommercePurchasedWithAttribute implements Filter {
  const ACTION = 'purchasedWithAttribute';

  private WooFilterHelper $wooFilterHelper;

  private FilterHelper $filterHelper;

  private Functions $wp;

  public function __construct(
    FilterHelper $filterHelper,
    WooFilterHelper $wooFilterHelper,
    Functions $wp
  ) {
    $this->wooFilterHelper = $wooFilterHelper;
    $this->filterHelper = $filterHelper;
    $this->wp = $wp;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $operator = $filterData->getOperator();
    $attributeTaxonomySlug = $filterData->getStringParam('attribute_taxonomy_slug');
    $attributeTermIds = $filterData->getArrayParam('attribute_term_ids');

    if ($operator === DynamicSegmentFilterData::OPERATOR_ANY) {
      $this->applyForAnyOperator($queryBuilder, $attributeTaxonomySlug, $attributeTermIds);
    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_ALL) {
      $this->applyForAnyOperator($queryBuilder, $attributeTaxonomySlug, $attributeTermIds);
      $countParam = $this->filterHelper->getUniqueParameterName('count');
      $queryBuilder
        ->groupBy('inner_subscriber_id')
        ->having("COUNT(DISTINCT attribute.term_id) = :$countParam")
        ->setParameter($countParam, count($attributeTermIds));
    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_NONE) {
      $subQuery = $this->filterHelper->getNewSubscribersQueryBuilder();
      $this->applyForAnyOperator($subQuery, $attributeTaxonomySlug, $attributeTermIds);
      $subscribersTable = $this->filterHelper->getSubscribersTable();
      $queryBuilder->where("{$subscribersTable}.id NOT IN ({$this->filterHelper->getInterpolatedSQL($subQuery)})");
    }

    return $queryBuilder;
  }

  private function applyForAnyOperator(QueryBuilder $queryBuilder, string $attributeTaxonomySlug, array $attributeTermIds): void {
    $termIdsParam = $this->filterHelper->getUniqueParameterName('attribute_term_ids');
    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder);
    $productAlias = $this->applyProductJoin($queryBuilder, $orderStatsAlias);
    $attributeAlias = $this->applyAttributeJoin($queryBuilder, $productAlias, $attributeTaxonomySlug);
    $queryBuilder->andWhere("$attributeAlias.term_id IN (:$termIdsParam)");
    $queryBuilder->setParameter($termIdsParam, $attributeTermIds, Connection::PARAM_STR_ARRAY);
  }

  private function applyProductJoin(QueryBuilder $queryBuilder, string $orderStatsAlias, string $alias = 'product'): string {
    $queryBuilder->innerJoin(
      $orderStatsAlias,
      $this->filterHelper->getPrefixedTable('wc_order_product_lookup'),
      $alias,
      "$orderStatsAlias.order_id = product.order_id"
    );
    return $alias;
  }

  private function applyAttributeJoin(QueryBuilder $queryBuilder, string $productAlias, $taxonomySlug, string $alias = 'attribute'): string {
    $queryBuilder->innerJoin(
      $productAlias,
      $this->filterHelper->getPrefixedTable('wc_product_attributes_lookup'),
      $alias,
      "product.product_id = attribute.product_id AND attribute.taxonomy = '$taxonomySlug'"
    );

    return $alias;
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    $slug = $filterData->getStringParam('attribute_taxonomy_slug');

    $lookupData = [
      'attribute' => $slug,
    ];

    $termIds = $filterData->getArrayParam('attribute_term_ids');
    $terms = $this->wp->getTerms([
      'taxonomy' => $slug,
      'include' => $termIds,
      'hide_empty' => false,
    ]);

    $lookupData['terms'] = array_map(function($term) {
      return $term->name;
    }, $terms);

    return $lookupData;
  }
}
