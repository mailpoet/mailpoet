<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommerceCountry implements Filter {
  const ACTION_CUSTOMER_COUNTRY = 'customerInCountry';

  /** @var EntityManager */
  private $entityManager;

  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    global $wpdb;
    $filterData = $filter->getFilterData();
    $countryCode = (string)$filterData->getParam('country_code');
    $countryFilterParam = 'countryCode' . $filter->getId();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $queryBuilder->innerJoin(
      $subscribersTable,
      $wpdb->postmeta,
      'postmeta',
      "postmeta.meta_key = '_customer_user'
        AND $subscribersTable.wp_user_id=postmeta.meta_value
        AND postmeta.post_id NOT IN ( SELECT id FROM {$wpdb->posts} as p WHERE p.post_status IN ('wc-cancelled', 'wc-failed'))"
    )->innerJoin('postmeta',
      $wpdb->postmeta,
      'postmetaCountry',
      "postmeta.post_id = postmetaCountry.post_id AND postmetaCountry.meta_key = '_billing_country' AND postmetaCountry.meta_value = :$countryFilterParam"
    )->setParameter($countryFilterParam, $countryCode);
  }
}
