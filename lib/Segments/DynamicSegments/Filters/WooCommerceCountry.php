<?php declare(strict_types = 1);

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
      "postmeta.meta_key = '_customer_user' AND $subscribersTable.wp_user_id=postmeta.meta_value"
    )->innerJoin(
      'postmeta',
      $wpdb->posts,
      'posts',
      "postmeta.post_id = posts.id AND posts.post_status NOT IN ('wc-cancelled', 'wc-failed')"
    )->innerJoin(
      'postmeta',
      $wpdb->postmeta,
      'postmetaCountry',
      "postmeta.post_id = postmetaCountry.post_id AND postmetaCountry.meta_key = '_billing_country' AND postmetaCountry.meta_value = :$countryFilterParam"
    )->setParameter($countryFilterParam, $countryCode);
  }
}
