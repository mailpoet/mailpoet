<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommerceProduct implements Filter {
  const ACTION_PRODUCT = 'purchasedProduct';

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    global $wpdb;
    $filterData = $filter->getFilterData();
    $operator = $filterData->getOperator();
    $productIds = $filterData->getParam('product_ids');
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();
    $completedOrder = "postmeta.post_id NOT IN ( SELECT id FROM {$wpdb->posts} AS p WHERE p.post_status IN ('wc-cancelled', 'wc-failed'))";

    if ($operator === DynamicSegmentFilterData::OPERATOR_ANY) {
      $this->applyPostmetaJoin($queryBuilder);
      $this->applyOrderItemsJoin($queryBuilder);
      $this->applyOrderItemmetaJoin($queryBuilder);
      $queryBuilder->where("itemmeta.meta_value IN (:products_{$parameterSuffix})");

    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_ALL) {
      $this->applyPostmetaJoin($queryBuilder);
      $this->applyOrderItemsJoin($queryBuilder);
      $this->applyOrderItemmetaJoin($queryBuilder);
      $queryBuilder->where("itemmeta.meta_value IN (:products_{$parameterSuffix})")
        ->groupBy("{$subscribersTable}.id, items.order_id")
        ->having('COUNT(items.order_id) = :count')
        ->setParameter('count', count($productIds));

    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_NONE) {
      $this->applyPostmetaJoin($queryBuilder);
      $this->applyOrderItemsJoin($queryBuilder);
      // subQuery with subscriber ids that bought products
      $subQuery = $this->createQueryBuilder($subscribersTable);
      $subQuery->select("DISTINCT $subscribersTable.id");
      $subQuery = $this->applyPostmetaJoin($subQuery);
      $subQuery = $this->applyOrderItemsJoin($subQuery);
      $subQuery = $this->applyOrderItemmetaJoin($subQuery);
      $subQuery->where("itemmeta.meta_value IN (:products_{$parameterSuffix})")
        ->andWhere($completedOrder);
      // application subQuery for negation
      $queryBuilder->where("{$subscribersTable}.id NOT IN ({$subQuery->getSQL()})");
    }
    return $queryBuilder
      ->andWhere($completedOrder)
      ->setParameter("products_{$parameterSuffix}", $productIds, Connection::PARAM_STR_ARRAY);
  }

  private function applyPostmetaJoin(QueryBuilder $queryBuilder): QueryBuilder {
    global $wpdb;
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
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
      "itemmeta.order_item_id=items.order_item_id AND itemmeta.meta_key='_product_id'"
    );
  }

  private function createQueryBuilder(string $table): QueryBuilder {
    return $this->entityManager->getConnection()
      ->createQueryBuilder()
      ->from($table);
  }
}
