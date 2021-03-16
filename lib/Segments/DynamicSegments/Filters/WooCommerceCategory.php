<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;
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
    $categoryId = (int)$filterData->getParam('category_id');
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $queryBuilder->innerJoin(
      $subscribersTable,
      $wpdb->postmeta,
      'postmeta',
      "postmeta.meta_key = '_customer_user' AND $subscribersTable.wp_user_id=postmeta.meta_value"
    )->join(
      'postmeta',
      $wpdb->prefix . 'woocommerce_order_items',
      'items',
      'postmeta.post_id = items.order_id'
    )->innerJoin(
      'items',
      $wpdb->prefix . 'woocommerce_order_itemmeta',
      'itemmeta',
      "itemmeta.order_item_id = items.order_item_id AND itemmeta.meta_key = '_product_id'"
    )->join(
      'itemmeta',
      $wpdb->term_relationships, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      'term_relationships',
      'itemmeta.meta_value = term_relationships.object_id'
    )->innerJoin(
      'term_relationships',
      $wpdb->term_taxonomy, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      'term_taxonomy',
      'term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id
      AND
      term_taxonomy.term_id IN (' . join(',', $this->getAllCategoryIds($categoryId)) . ')'
    )->andWhere('postmeta.post_id NOT IN (
        SELECT id FROM ' . $wpdb->posts . ' as p WHERE p.post_status IN ("wc-cancelled", "wc-failed")
      )'
    );
  }

  private function getAllCategoryIds(int $categoryId) {
    $subcategories = $this->wp->getTerms('product_cat', ['child_of' => $categoryId]);
    if (!is_array($subcategories)) return [];
    $ids = array_map(function($category) {
      return $category->term_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    }, $subcategories);
    $ids[] = $categoryId;
    return $ids;
  }
}
