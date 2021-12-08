<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\DBCollationChecker;
use MailPoet\Util\Security;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommerceNumberOfOrders implements Filter {
  const ACTION_NUMBER_OF_ORDERS = 'numberOfOrders';

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
    global $wpdb;
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $filterData = $filter->getFilterData();
    $type = $filterData->getParam('number_of_orders_type');
    $count = $filterData->getParam('number_of_orders_count');
    $days = $filterData->getParam('number_of_orders_days');
    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();
    $collation = $this->collationChecker->getCollateIfNeeded(
      $subscribersTable,
      'email',
      $wpdb->prefix . 'wc_customer_lookup',
      'email'
    );

    $date = Carbon::now()->subDays($days);

    $queryBuilder->innerJoin(
      $subscribersTable,
      $wpdb->prefix . 'wc_customer_lookup',
      'customer',
      "$subscribersTable.email = customer.email $collation"
    )->leftJoin(
      'customer',
      $wpdb->prefix . 'wc_order_stats',
      'orderStats',
      'customer.customer_id = orderStats.customer_id AND orderStats.date_created >= :date' . $parameterSuffix . ' AND orderStats.status NOT IN ("wc-cancelled", "wc-failed")'
    )->setParameter('date' . $parameterSuffix, $date->toDateTimeString())
      ->groupBy('inner_subscriber_id');

    if ($type === '=') {
      $queryBuilder->having('COUNT(orderStats.order_id) = :count' . $parameterSuffix);
    } elseif ($type === '!=') {
      $queryBuilder->having('COUNT(orderStats.order_id) != :count' . $parameterSuffix);
    } elseif ($type === '>') {
      $queryBuilder->having('COUNT(orderStats.order_id) > :count' . $parameterSuffix);
    } elseif ($type === '<') {
      $queryBuilder->having('COUNT(orderStats.order_id) < :count' . $parameterSuffix);
    }

    $queryBuilder->setParameter('count' . $parameterSuffix, $count);

    return $queryBuilder;
  }
}
