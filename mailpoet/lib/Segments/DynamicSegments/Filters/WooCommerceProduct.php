<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\DBCollationChecker;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommerceProduct implements Filter {
  const ACTION_PRODUCT = 'purchasedProduct';

  /** @var EntityManager */
  private $entityManager;

  /** @var DBCollationChecker */
  private $collationChecker;

  public function __construct(
    EntityManager $entityManager,
    DBCollationChecker $collationChecker
  ) {
    $this->entityManager = $entityManager;
    $this->collationChecker = $collationChecker;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $operator = $filterData->getOperator();
    $productIds = $filterData->getParam('product_ids');
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();

    if ($operator === DynamicSegmentFilterData::OPERATOR_ANY) {
      $this->applyCustomerJoin($queryBuilder);
      $this->applyOrderJoin($queryBuilder);
      $this->applyProductJoin($queryBuilder);
      $queryBuilder->where("product.product_id IN (:products_{$parameterSuffix})");

    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_ALL) {
      $this->applyCustomerJoin($queryBuilder);
      $this->applyOrderJoin($queryBuilder);
      $this->applyProductJoin($queryBuilder);
      $queryBuilder->where("product.product_id IN (:products_{$parameterSuffix})")
        ->groupBy("{$subscribersTable}.id, orderStats.order_id")
        ->having('COUNT(orderStats.order_id) = :count' . $parameterSuffix)
        ->setParameter('count' . $parameterSuffix, count($productIds));

    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_NONE) {
      // subQuery with subscriber ids that bought products
      $subQuery = $this->createQueryBuilder($subscribersTable);
      $subQuery->select("DISTINCT $subscribersTable.id");
      $subQuery = $this->applyCustomerJoin($subQuery);
      $subQuery = $this->applyOrderJoin($subQuery);
      $subQuery = $this->applyProductJoin($subQuery);
      $subQuery->where("product.product_id IN (:products_{$parameterSuffix})");
      // application subQuery for negation
      $queryBuilder->where("{$subscribersTable}.id NOT IN ({$subQuery->getSQL()})");
    }
    return $queryBuilder
      ->setParameter("products_{$parameterSuffix}", $productIds, Connection::PARAM_STR_ARRAY);
  }

  private function applyCustomerJoin(QueryBuilder $queryBuilder): QueryBuilder {
    global $wpdb;
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
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

  private function applyOrderJoin(QueryBuilder $queryBuilder): QueryBuilder {
    global $wpdb;
    return $queryBuilder->join(
      'customer',
      $wpdb->prefix . 'wc_order_stats',
      'orderStats',
      'customer.customer_id = orderStats.customer_id AND orderStats.status NOT IN ("wc-cancelled", "wc-failed")'
    );
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

  private function createQueryBuilder(string $table): QueryBuilder {
    return $this->entityManager->getConnection()
      ->createQueryBuilder()
      ->from($table);
  }
}
