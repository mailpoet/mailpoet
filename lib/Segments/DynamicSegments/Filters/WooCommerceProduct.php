<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommerceProduct implements Filter {
  const ACTION_PRODUCT = 'purchasedProduct';

  /** @var EntityManager */
  private $entityManager;

  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filterEntity): QueryBuilder {
    global $wpdb;
    $productId = (int)$filterEntity->getFilterDataParam('product_id');
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $queryBuilder->innerJoin(
      $subscribersTable,
      $wpdb->postmeta,
      'postmeta',
      "postmeta.meta_key = '_customer_user' AND $subscribersTable.wp_user_id=postmeta.meta_value"
    )->join('postmeta',
      $wpdb->prefix . 'woocommerce_order_items',
      'items',
      'postmeta.post_id = items.order_id'
    )->innerJoin(
      'items',
      $wpdb->prefix . 'woocommerce_order_itemmeta',
      'itemmeta',
      "itemmeta.order_item_id=items.order_item_id AND itemmeta.meta_key='_product_id' AND itemmeta.meta_value=:product"
    )->andWhere(
      $subscribersTable . '.status = :status AND
      postmeta.post_id NOT IN ( SELECT id FROM ' . $wpdb->posts . ' as p WHERE p.post_status IN ("wc-cancelled", "wc-failed"))'
    )->setParameter('product', $productId
    )->setParameter('status', SubscriberEntity::STATUS_SUBSCRIBED);
  }
}
