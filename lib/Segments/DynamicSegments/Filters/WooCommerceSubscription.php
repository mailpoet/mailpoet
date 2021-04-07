<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommerceSubscription implements Filter {
  const ACTION_HAS_ACTIVE = 'hasActiveSubscription';

  /** @var EntityManager */
  private $entityManager;

  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    global $wpdb;
    $filterData = $filter->getFilterData();
    $productId = (int)$filterData->getParam('product_id');
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $queryBuilder->innerJoin(
      $subscribersTable,
      $wpdb->postmeta,
      'postmeta',
      "postmeta.meta_key = '_customer_user' AND $subscribersTable.wp_user_id=postmeta.meta_value"
    )->innerJoin('postmeta',
      $wpdb->posts,
        'posts',
        'postmeta.post_id = posts.id AND posts.post_type = "shop_subscription" AND posts.post_status = "wc-active"'
    )->innerJoin('postmeta',
      $wpdb->prefix . 'woocommerce_order_items',
      'items',
      'postmeta.post_id = items.order_id'
    )->innerJoin(
      'items',
      $wpdb->prefix . 'woocommerce_order_itemmeta',
      'itemmeta',
      "itemmeta.order_item_id=items.order_item_id AND itemmeta.meta_key='_product_id' AND itemmeta.meta_value=:product" . $filter->getId()
    )->setParameter('product' . $filter->getId(), $productId);
  }
}
