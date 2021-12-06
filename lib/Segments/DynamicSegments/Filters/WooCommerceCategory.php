<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommerceCategory implements Filter {
  const ACTION_CATEGORY = 'purchasedCategory';

  const ACTION_PRODUCT = 'purchasedProduct';

  /** @var EntityManager */
  private $entityManager;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    EntityManager $entityManager,
    WPFunctions $wp
  ) {
    $this->entityManager = $entityManager;
    $this->wp = $wp;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    global $wpdb;
    $filterData = $filter->getFilterData();

    $operator = $filterData->getOperator();
    $categoryIds = (array)$filterData->getParam('category_ids');
    $categoryIdswithChildrenIds = $this->getCategoriesWithChildren($categoryIds);

    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    $parameterSuffix = $filter->getId() ?: Security::generateRandomString();
    $parameterSuffix = (string)$parameterSuffix;

    $completedOrder = "postmeta.post_id NOT IN ( SELECT id FROM {$wpdb->posts} AS p WHERE p.post_status IN ('wc-cancelled', 'wc-failed'))";

    if ($operator === DynamicSegmentFilterData::OPERATOR_ANY) {
      $this->applyPostmetaJoin($queryBuilder, $subscribersTable);
      $this->applyOrderItemsJoin($queryBuilder);
      $this->applyOrderItemmetaJoin($queryBuilder);
      $this->applyTermRelationshipsJoin($queryBuilder);
      $this->applyTermTaxonomyJoin($queryBuilder, $parameterSuffix);

    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_ALL) {
      $this->applyPostmetaJoin($queryBuilder, $subscribersTable);
      $this->applyOrderItemsJoin($queryBuilder);
      $this->applyOrderItemmetaJoin($queryBuilder);
      $this->applyTermRelationshipsJoin($queryBuilder);
      $this->applyTermTaxonomyJoin($queryBuilder, $parameterSuffix)
      ->groupBy("{$subscribersTable}.id, items.order_id")
      ->having('COUNT(items.order_id) = :count')
      ->setParameter('count', count($categoryIds));

    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_NONE) {
      $this->applyPostmetaJoin($queryBuilder, $subscribersTable);
      $this->applyOrderItemsJoin($queryBuilder);
      // subQuery with subscriber ids that bought products
      $subQuery = $this->createQueryBuilder($subscribersTable);
      $subQuery->select("DISTINCT $subscribersTable.id");
      $subQuery = $this->applyPostmetaJoin($subQuery, $subscribersTable);
      $subQuery = $this->applyOrderItemsJoin($subQuery);
      $subQuery = $this->applyOrderItemmetaJoin($subQuery);
      $subQuery = $this->applyTermRelationshipsJoin($subQuery);
      $subQuery = $this->applyTermTaxonomyJoin($subQuery, $parameterSuffix)
      ->andWhere($completedOrder);
      // application subQuery for negation
      $queryBuilder->where("{$subscribersTable}.id NOT IN ({$subQuery->getSQL()})");
    }

    return $queryBuilder
      ->andWhere($completedOrder)
      ->setParameter("category_{$parameterSuffix}", $categoryIdswithChildrenIds, Connection::PARAM_STR_ARRAY);
  }

  private function applyPostmetaJoin(QueryBuilder $queryBuilder, string $subscribersTable): QueryBuilder {
    global $wpdb;
    return $queryBuilder->innerJoin(
      $subscribersTable,
      $wpdb->postmeta,
      'postmeta',
      "postmeta.meta_key = '_customer_user' AND $subscribersTable.wp_user_id=postmeta.meta_value"
    );
  }

  private function applyOrderItemsJoin(QueryBuilder $queryBuilder): QueryBuilder {
    global $wpdb;
    return $queryBuilder->join('postmeta',
      $wpdb->prefix . 'woocommerce_order_items',
      'items',
      'postmeta.post_id = items.order_id'
    );
  }

  private function applyOrderItemmetaJoin(QueryBuilder $queryBuilder): QueryBuilder {
    global $wpdb;
    return $queryBuilder->innerJoin(
      'items',
      $wpdb->prefix . 'woocommerce_order_itemmeta',
      'itemmeta',
      "itemmeta.order_item_id = items.order_item_id AND itemmeta.meta_key = '_product_id'"
    );
  }

  private function applyTermRelationshipsJoin(QueryBuilder $queryBuilder): QueryBuilder {
    global $wpdb;
    return $queryBuilder->join(
      'itemmeta',
      $wpdb->term_relationships, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'term_relationships',
      'itemmeta.meta_value = term_relationships.object_id'
    );
  }

  private function applyTermTaxonomyJoin(QueryBuilder $queryBuilder, string $parameterSuffix): QueryBuilder {
    global $wpdb;
    return $queryBuilder->innerJoin(
      'term_relationships',
      $wpdb->term_taxonomy, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'term_taxonomy',
      "term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id
      AND
      term_taxonomy.term_id IN (:category_{$parameterSuffix})"
    );
  }

  private function createQueryBuilder(string $table): QueryBuilder {
    return $this->entityManager->getConnection()
      ->createQueryBuilder()
      ->from($table);
  }

  private function getCategoriesWithChildren(array $categoriesId) {
    $allIds = [];

    foreach ($categoriesId as $categoryId) {
      $allIds = array_merge($allIds, $this->getAllCategoryIds($categoryId));
    }

    return array_unique($allIds);
  }

  private function getAllCategoryIds(int $categoryId) {
    $subcategories = $this->wp->getTerms('product_cat', ['child_of' => $categoryId]);
    if (!is_array($subcategories) || empty($subcategories)) return [$categoryId];
    $ids = array_map(function($category) {
      return $category->term_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }, $subcategories);
    $ids[] = $categoryId;
    return $ids;
  }
}
