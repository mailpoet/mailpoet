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
      $wpdb->postmeta,
      'meta_value'
    );

    $date = Carbon::now()->subDays($days);

    $queryBuilder->innerJoin(
      $subscribersTable,
      $wpdb->postmeta,
      'postmeta',
      "postmeta.meta_key = '_billing_email' AND $subscribersTable.email = postmeta.meta_value $collation AND $subscribersTable.is_woocommerce_user = 1"
    )->leftJoin(
      'postmeta',
      $wpdb->posts,
      'posts',
      'posts.ID = postmeta.post_id AND posts.post_date >= :date' . $parameterSuffix . ' AND postmeta.post_id NOT IN ( SELECT id FROM ' . $wpdb->posts . ' as p WHERE p.post_status IN ("wc-cancelled", "wc-failed"))'
    )->setParameter(
      'date' . $parameterSuffix, $date->toDateTimeString()
    )->groupBy(
      'inner_subscriber_id'
    );

    if ($type === '=') {
      $queryBuilder->having('COUNT(posts.ID) = :count' . $parameterSuffix);
    } elseif ($type === '>') {
      $queryBuilder->having('COUNT(posts.ID) > :count' . $parameterSuffix);
    } elseif ($type === '<') {
      $queryBuilder->having('COUNT(posts.ID) < :count' . $parameterSuffix);
    }

    $queryBuilder->setParameter('count' . $parameterSuffix, $count);

    return $queryBuilder;
  }
}
