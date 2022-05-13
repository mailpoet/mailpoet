<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\DBCollationChecker;
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

  /** @var DBCollationChecker */
  private $collationChecker;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    EntityManager $entityManager,
    DBCollationChecker $collationChecker,
    WPFunctions $wp
  ) {
    $this->entityManager = $entityManager;
    $this->collationChecker = $collationChecker;
    $this->wp = $wp;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();

    $operator = $filterData->getOperator();
    $categoryIds = (array)$filterData->getParam('category_ids');
    $categoryIdswithChildrenIds = $this->getCategoriesWithChildren($categoryIds);

    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    $parameterSuffix = $filter->getId() ?: Security::generateRandomString();
    $parameterSuffix = (string)$parameterSuffix;

    if ($operator === DynamicSegmentFilterData::OPERATOR_ANY) {
      $this->applyCustomerJoin($queryBuilder, $subscribersTable);
      $this->applyOrderStatsJoin($queryBuilder);
      $this->applyProductJoin($queryBuilder);
      $this->applyTermRelationshipsJoin($queryBuilder);
      $this->applyTermTaxonomyJoin($queryBuilder, $parameterSuffix);

    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_ALL) {
      $this->applyCustomerJoin($queryBuilder, $subscribersTable);
      $this->applyOrderStatsJoin($queryBuilder);
      $this->applyProductJoin($queryBuilder);
      $this->applyTermRelationshipsJoin($queryBuilder);
      $this->applyTermTaxonomyJoin($queryBuilder, $parameterSuffix)
        ->groupBy("{$subscribersTable}.id, orderStats.order_id")
        ->having('COUNT(orderStats.order_id) = :count_' . $parameterSuffix)
        ->setParameter('count_' . $parameterSuffix, count($categoryIds));

    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_NONE) {
      // subQuery with subscriber ids that bought products
      $subQuery = $this->createQueryBuilder($subscribersTable);
      $subQuery->select("DISTINCT $subscribersTable.id");
      $subQuery = $this->applyCustomerJoin($subQuery, $subscribersTable);
      $subQuery = $this->applyOrderStatsJoin($subQuery);
      $subQuery = $this->applyProductJoin($subQuery);
      $subQuery = $this->applyTermRelationshipsJoin($subQuery);
      $subQuery = $this->applyTermTaxonomyJoin($subQuery, $parameterSuffix);
      // application subQuery for negation
      $queryBuilder->where("{$subscribersTable}.id NOT IN ({$subQuery->getSQL()})");
    }

    return $queryBuilder
      ->setParameter("category_{$parameterSuffix}", $categoryIdswithChildrenIds, Connection::PARAM_STR_ARRAY);
  }

  private function applyCustomerJoin(QueryBuilder $queryBuilder, string $subscribersTable): QueryBuilder {
    global $wpdb;
    $collation = $this->collationChecker->getCollateIfNeeded(
      $subscribersTable,
      'email',
      $wpdb->prefix . 'wc_customer_lookup',
      'email'
    );
    return $queryBuilder->innerJoin(
      $subscribersTable,
      $wpdb->prefix . 'wc_customer_lookup',
      'customer',
      "$subscribersTable.email = customer.email $collation"
    );
  }

  private function applyOrderStatsJoin(QueryBuilder $queryBuilder): QueryBuilder {
    global $wpdb;
    return $queryBuilder
      ->join(
        'customer',
        $wpdb->prefix . 'wc_order_stats',
        'orderStats',
        'customer.customer_id = orderStats.customer_id'
      )
      ->andWhere("orderStats.status NOT IN ('wc-cancelled', 'wc-on-hold', 'wc-pending', 'wc-failed')");
  }

  private function applyProductJoin(QueryBuilder $queryBuilder): QueryBuilder {
    global $wpdb;
    return $queryBuilder->innerJoin(
      'orderStats',
      $wpdb->prefix . 'wc_order_product_lookup',
      'product',
      'orderStats.order_id = product.order_id'
    );
  }

  private function applyTermRelationshipsJoin(QueryBuilder $queryBuilder): QueryBuilder {
    global $wpdb;
    return $queryBuilder->join(
      'product',
      $wpdb->term_relationships, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'term_relationships',
      'product.product_id = term_relationships.object_id'
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

  private function getCategoriesWithChildren(array $categoriesId): array {
    $allIds = [];

    foreach ($categoriesId as $categoryId) {
      $allIds = array_merge($allIds, $this->getAllCategoryIds($categoryId));
    }

    return array_unique($allIds);
  }

  private function getAllCategoryIds(int $categoryId): array {
    $subcategories = $this->wp->getTerms('product_cat', ['child_of' => $categoryId]);
    if (!is_array($subcategories) || empty($subcategories)) return [$categoryId];
    $ids = array_map(function($category) {
      return $category->term_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }, $subcategories);
    $ids[] = $categoryId;
    return $ids;
  }
}
