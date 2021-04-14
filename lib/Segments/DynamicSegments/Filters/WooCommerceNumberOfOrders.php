<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommerceNumberOfOrders implements Filter {
  const ACTION_NUMBER_OF_ORDERS = 'numberOfOrders';

  /** @var EntityManager */
  private $entityManager;

  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    global $wpdb;
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $filterData = $filter->getFilterData();
    $type = $filterData->getParam('number_of_orders_type');
    $count = $filterData->getParam('number_of_orders_count');
    $days = $filterData->getParam('number_of_orders_days');

    $date = Carbon::now()->subDays($days);

    $queryBuilder->innerJoin(
      $subscribersTable,
      $wpdb->postmeta,
      'postmeta',
      "postmeta.meta_key = '_customer_user' AND $subscribersTable.wp_user_id=postmeta.meta_value"
    )->leftJoin(
      'postmeta',
      $wpdb->posts,
      'posts',
      'posts.ID = postmeta.post_id AND posts.post_date >= :date' . $filter->getId() . ' AND postmeta.post_id NOT IN ( SELECT id FROM ' . $wpdb->posts . ' as p WHERE p.post_status IN ("wc-cancelled", "wc-failed"))'
    )->setParameter(
      'date' . $filter->getId(), $date->toDateTimeString()
    )->groupBy(
      'inner_subscriber_id'
    );

    if ($type === '=') {
      $queryBuilder->having('COUNT(posts.ID) = :count' . $filter->getId());
    } elseif ($type === '>') {
      $queryBuilder->having('COUNT(posts.ID) > :count' . $filter->getId());
    } elseif ($type === '<') {
      $queryBuilder->having('COUNT(posts.ID) < :count' . $filter->getId());
    }

    $queryBuilder->setParameter('count' . $filter->getId(), $count);

    return $queryBuilder;
  }
}
